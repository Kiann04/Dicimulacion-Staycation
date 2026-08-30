<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\InvalidBookingTransition;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * The single owner of booking and payment status changes.
 *
 * Every transition loads and locks the booking row inside its own transaction
 * and validates against *that* row, never against the model instance the caller
 * happened to be holding. A stale instance is exactly how two administrators
 * clicking at the same moment both pass a "current status" check and both write.
 *
 * Locking convention (shared with BookingInventoryService):
 *
 *   1. staycation rows, ascending id
 *   2. booking row
 *
 * This service touches no inventory, so it only ever takes the booking lock;
 * taking it last keeps it consistent with the inventory paths and avoids the
 * lock-order inversion that would otherwise deadlock the two against each other.
 *
 * Two rules drive the behaviour:
 *
 *  - `amount_paid` is money an administrator has verified as received. A
 *    customer uploading a proof verifies nothing, so a fresh booking sits at
 *    `pending` with nothing paid and the claimed figure in `declared_amount`.
 *  - Payment state and booking state are independent. Marking a booking unpaid
 *    records a failed verification; it does not cancel the stay.
 */
class BookingPaymentService
{
    /**
     * Retries when the database reports a deadlock. Laravel re-runs the whole
     * closure, which re-reads and re-locks, so the retry is safe.
     */
    private const DEADLOCK_ATTEMPTS = 3;

    /**
     * Record an administrator's verification decision.
     *
     * @throws InvalidBookingTransition
     */
    public function verifyPayment(Booking|int $booking, PaymentStatus $target): Booking
    {
        return $this->withLockedBooking($booking, function (Booking $locked) use ($target): Booking {
            $current = $locked->paymentStatus();

            if ($current !== null && ! $current->canTransitionTo($target)) {
                throw InvalidBookingTransition::forPayment($current, $target);
            }

            $verified = $target->verifiedAmountFor($locked->totalPrice());

            $attributes = [
                'payment_status' => $target->value,
                'amount_paid' => $verified->toDecimalString(),
            ];

            // Verified money confirms the stay. Nothing else about a payment
            // decision may move the booking's own status.
            if ($target->isVerified()) {
                $bookingStatus = $this->requireKnownStatus($locked);

                if (! $bookingStatus->canTransitionTo(BookingStatus::Confirmed)) {
                    throw InvalidBookingTransition::forBooking($bookingStatus, BookingStatus::Confirmed);
                }

                $attributes['status'] = BookingStatus::Confirmed->value;
            }

            $locked->update($attributes);

            $this->log(
                $target->isVerified() ? 'Payment Verified' : 'Payment Status Updated',
                "Booking ID: {$locked->getKey()} ({$locked->staycation?->house_name}) recorded as "
                    .ucwords(str_replace('_', ' ', $target->value))
                    .' with '.$verified->toDecimalString().' verified.'
            );

            return $locked;
        });
    }

    /**
     * Settle the outstanding half of a partially paid booking.
     *
     * The half-paid precondition is checked against the locked row, so two
     * simultaneous clicks cannot both decide the booking was half paid.
     *
     * @throws InvalidBookingTransition
     */
    public function settleRemainingBalance(Booking|int $booking): Booking
    {
        return $this->withLockedBooking($booking, function (Booking $locked): Booking {
            if ($locked->paymentStatus() !== PaymentStatus::HalfPaid) {
                throw new InvalidBookingTransition('Only half-paid bookings can be marked as fully paid.');
            }

            // Settling a balance is a payment action, not a reopen decision. A
            // cancelled, declined or completed stay stays that way.
            $bookingStatus = $this->requireKnownStatus($locked);

            if ($bookingStatus->isTerminal()) {
                throw InvalidBookingTransition::forTerminalBooking($bookingStatus, 'settled');
            }

            if (! $bookingStatus->canTransitionTo(BookingStatus::Confirmed)) {
                throw InvalidBookingTransition::forBooking($bookingStatus, BookingStatus::Confirmed);
            }

            $total = $locked->totalPrice();

            $locked->update([
                'payment_status' => PaymentStatus::Paid->value,
                'status' => BookingStatus::Confirmed->value,
                'amount_paid' => $total->toDecimalString(),
            ]);

            $this->log(
                'Payment Verified',
                "Booking ID: {$locked->getKey()} ({$locked->staycation?->house_name}) settled in full "
                    ."with {$total->toDecimalString()} verified."
            );

            return $locked;
        });
    }

    /**
     * Approve a booking so the customer may settle payment.
     *
     * @throws InvalidBookingTransition
     */
    public function approve(Booking|int $booking): Booking
    {
        return $this->moveBookingTo($booking, BookingStatus::Approved, 'Booking Approved', function (Booking $locked): array {
            // Leave a verified payment alone; approving must not unwind money.
            if ($locked->paymentStatus()?->isVerified()) {
                return [];
            }

            return ['payment_status' => PaymentStatus::Pending->value];
        });
    }

    /**
     * Decline a booking, releasing its dates back to the calendar.
     *
     * @throws InvalidBookingTransition
     */
    public function decline(Booking|int $booking): Booking
    {
        return $this->moveBookingTo($booking, BookingStatus::Declined, 'Booking Declined', function (Booking $locked): array {
            $current = $locked->paymentStatus();

            // A declined booking that already took money keeps its record; the
            // money is a refund matter, not something to erase here.
            if ($current?->isVerified()) {
                return [];
            }

            if ($current !== null && ! $current->canTransitionTo(PaymentStatus::Failed)) {
                throw InvalidBookingTransition::forPayment($current, PaymentStatus::Failed);
            }

            return ['payment_status' => PaymentStatus::Failed->value];
        });
    }

    /**
     * Cancel a booking. Explicit, and independent of payment state.
     *
     * Any payment proof already on file is deliberately left in place: it is
     * evidence for the refund conversation that follows.
     *
     * @throws InvalidBookingTransition
     */
    public function cancel(Booking|int $booking, ?User $actor = null): Booking
    {
        return $this->moveBookingTo(
            $booking,
            BookingStatus::Cancelled,
            'Booking Cancelled',
            function (Booking $locked): array {
                if ($locked->paymentStatus()?->isVerified()) {
                    return [];
                }

                return ['payment_status' => PaymentStatus::Cancelled->value];
            },
            // Authorization depends on the booking's own status, so it has to be
            // decided from the locked row. A customer holding a page rendered
            // while their booking was still pending must not be able to cancel it
            // after an administrator has confirmed it and taken payment.
            $actor === null
                ? null
                : function (Booking $locked) use ($actor): void {
                    if (Gate::forUser($actor)->denies('cancel', $locked)) {
                        throw new AuthorizationException('This booking can no longer be cancelled.');
                    }
                },
        );
    }

    /**
     * Mark a stay that has finished as completed.
     *
     * Driven by the calendar rather than by a person, so it is the one transition
     * that legitimately has no signed-in actor; the audit row records a null user
     * to mean "the system".
     *
     * @throws InvalidBookingTransition
     */
    public function complete(Booking|int $booking): Booking
    {
        return $this->withLockedBooking($booking, function (Booking $locked): Booking {
            $current = $this->requireKnownStatus($locked);

            // The scheduler's query only nominates candidates. Between that scan
            // and this lock an administrator may have moved the stay into the
            // future or cancelled it, so both conditions are re-read here from
            // the locked row before anything is written.
            if (! in_array($current, [BookingStatus::Approved, BookingStatus::Confirmed], true)) {
                throw InvalidBookingTransition::forBooking($current, BookingStatus::Completed);
            }

            if (! $this->hasFinished($locked)) {
                throw new InvalidBookingTransition(
                    "Booking {$locked->getKey()} has not finished yet and cannot be completed."
                );
            }

            $locked->update(['status' => BookingStatus::Completed->value]);

            $this->log('Booking Completed', "Booking ID: {$locked->getKey()} moved to completed.");

            return $locked;
        });
    }

    /**
     * Whether the stay's checkout day is already behind us.
     *
     * Evaluated in the application's business timezone (Asia/Manila) so a stay
     * is never completed early for a viewer in another zone.
     */
    private function hasFinished(Booking $booking): bool
    {
        if ($booking->end_date === null) {
            return false;
        }

        return CarbonImmutable::parse($booking->end_date)->startOfDay()
            ->lessThan(CarbonImmutable::today());
    }

    /**
     * @param  callable(Booking): array<string, mixed>  $paymentAttributes
     *
     * @throws InvalidBookingTransition
     */
    private function moveBookingTo(
        Booking|int $booking,
        BookingStatus $target,
        string $auditAction,
        callable $paymentAttributes,
        ?callable $authorize = null,
    ): Booking {
        return $this->withLockedBooking($booking, function (Booking $locked) use ($target, $auditAction, $paymentAttributes, $authorize): Booking {
            if ($authorize !== null) {
                $authorize($locked);
            }

            $current = $this->requireKnownStatus($locked);

            if (! $current->canTransitionTo($target)) {
                throw InvalidBookingTransition::forBooking($current, $target);
            }

            $locked->update(array_merge(
                ['status' => $target->value],
                $paymentAttributes($locked),
            ));

            $this->log($auditAction, "Booking ID: {$locked->getKey()} moved to {$target->value}.");

            return $locked;
        });
    }

    /**
     * The booking's status, or a refusal to act on a value we do not recognise.
     *
     * A row whose status resolves to nothing known is corrupt or predates this
     * status model. Treating that as a blank slate would let an ordinary admin
     * action decide what it meant; the record needs auditing first.
     *
     * @throws InvalidBookingTransition
     */
    private function requireKnownStatus(Booking $booking): BookingStatus
    {
        return $booking->bookingStatus()
            ?? throw InvalidBookingTransition::forUnknownBookingStatus($booking->status);
    }

    /**
     * Run a transition against the locked, authoritative booking row.
     *
     * When the caller handed us a model instance, its attributes are refreshed
     * from the locked row afterwards so the caller is never left reading state
     * the transition has already superseded.
     *
     * @param  callable(Booking): Booking  $callback
     */
    private function withLockedBooking(Booking|int $booking, callable $callback): Booking
    {
        $bookingId = $booking instanceof Booking ? $booking->getKey() : $booking;

        $updated = DB::transaction(function () use ($bookingId, $callback): Booking {
            $locked = Booking::query()
                ->with(['user', 'staycation'])
                ->lockForUpdate()
                ->findOrFail($bookingId);

            return $callback($locked);
        }, self::DEADLOCK_ATTEMPTS);

        if ($booking instanceof Booking) {
            $booking->setRawAttributes($updated->getAttributes(), true);
        }

        return $updated;
    }

    private function log(string $action, string $description): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
        ]);
    }
}
