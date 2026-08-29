<?php

namespace App\Services\Booking;

use App\Enums\BookingStatus;
use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\Payment;
use App\Models\Staycation;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Booking\Exceptions\BookingNotCancellableException;
use App\Services\Booking\Exceptions\StaycationNotBookableException;
use App\Services\Payment\PaymentProofStorage;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Owns the booking lifecycle. Both the Blade controllers and the v1 API call
 * into this class, so the availability, pricing and status rules exist once.
 */
class BookingService
{
    public function __construct(
        private BookingAvailabilityService $availability,
        private BookingPricingService $pricing,
        private PaymentProofStorage $proofStorage,
        private AuditLogger $audit,
    ) {}

    /**
     * Produces the authoritative price for a range without persisting anything.
     * Validates dates, stay length and guest capacity on the way through.
     */
    public function quote(Staycation $staycation, DateRange $range, int $guestNumber): BookingQuote
    {
        return $this->pricing->quote($staycation, $range, $guestNumber);
    }

    /**
     * Creates a booking under a transaction, re-checking availability *inside*
     * the lock so a range that was free when the customer opened the form cannot
     * be double-booked between preview and submission.
     *
     * Concurrency strategy for MySQL/InnoDB on shared hosting: the staycation row
     * is taken with SELECT ... FOR UPDATE, which serialises every concurrent
     * booking attempt for that staycation while leaving other staycations
     * untouched. This needs no Redis, no queue and no advisory-lock table, and the
     * lock is released as soon as the transaction commits. MySQL cannot express a
     * range-overlap constraint, so the row lock plus the in-transaction re-check
     * is what actually guarantees correctness.
     *
     * @throws Exceptions\BookingException
     */
    public function create(User $user, Staycation $staycation, BookingSubmission $submission): Booking
    {
        if (! $staycation->isBookable()) {
            throw new StaycationNotBookableException("{$staycation->house_name} is not currently accepting bookings.");
        }

        // Validate the cheap rules and price the stay before opening the
        // transaction, so a rejected request never holds a row lock.
        $quote = $this->pricing->quote($staycation, $submission->range, $submission->guestNumber);

        // Filesystem writes are not transactional, so the upload happens outside
        // the transaction and is cleaned up if the transaction fails.
        $proofPath = $submission->paymentProof !== null
            ? $this->proofStorage->store($submission->paymentProof)
            : null;

        try {
            return DB::transaction(function () use ($user, $staycation, $submission, $quote, $proofPath): Booking {
                /** @var Staycation $lockedStaycation */
                $lockedStaycation = Staycation::query()
                    ->whereKey($staycation->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->availability->assertAvailable($lockedStaycation, $submission->range);

                $amountDue = $quote->amountDueFor($submission->paymentType);

                $booking = Booking::create([
                    'staycation_id' => $lockedStaycation->getKey(),
                    'user_id' => $user->getKey(),
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $submission->phone,
                    'guest_number' => $submission->guestNumber,
                    'start_date' => $submission->range->startString(),
                    'end_date' => $submission->range->endString(),
                    'price_per_day' => $quote->pricePerNight,
                    'total_price' => $quote->totalPrice,
                    'amount_paid' => '0.00',
                    'payment_status' => PaymentStatus::Pending->value,
                    'payment_method' => $submission->paymentMethod,
                    'payment_proof' => $proofPath,
                    'transaction_number' => $submission->transactionNumber,
                    'message_to_admin' => $submission->messageToAdmin,
                    'status' => BookingStatus::Pending->value,
                ]);

                // Ledger entry for the money the customer says they have sent. It
                // stays pending until an admin verifies it, so amount_paid is never
                // credited on the customer's word alone.
                Payment::create([
                    'booking_id' => $booking->getKey(),
                    'amount' => $amountDue,
                    'type' => $submission->paymentType === 'half'
                        ? PaymentType::Deposit->value
                        : PaymentType::Full->value,
                    'payment_method' => $submission->paymentMethod,
                    'reference_number' => $submission->transactionNumber,
                    'proof_path' => $proofPath,
                    'status' => PaymentRecordStatus::Pending->value,
                ]);

                return $booking;
            });
        } catch (Throwable $exception) {
            $this->proofStorage->delete($proofPath);

            throw $exception;
        }
    }

    /**
     * Approves a request and moves it to awaiting-payment. Availability is
     * re-checked first: a range can be taken by another guest between submission
     * and review, and approving it anyway would create a genuine double booking.
     *
     * @throws Exceptions\DatesUnavailableException
     */
    public function approve(Booking $booking, User $actor): Booking
    {
        return DB::transaction(function () use ($booking, $actor): Booking {
            /** @var Staycation $staycation */
            $staycation = Staycation::query()
                ->whereKey($booking->staycation_id)
                ->lockForUpdate()
                ->firstOrFail();

            $range = DateRange::fromInput(
                $booking->start_date->toDateString(),
                $booking->end_date->toDateString(),
            );

            $this->availability->assertAvailable($staycation, $range, $booking->getKey());

            $booking->forceFill([
                'status' => BookingStatus::Approved->value,
                'payment_status' => PaymentStatus::Pending->value,
            ])->save();

            $this->audit->record($actor, 'Booking Approved', "Booking ID: {$booking->getKey()} approved and awaiting payment.");

            return $booking->refresh();
        });
    }

    /**
     * Declines a request. The declined status releases the dates immediately, so
     * the range becomes bookable again for someone else.
     */
    public function decline(Booking $booking, User $actor, ?string $reason = null): Booking
    {
        return DB::transaction(function () use ($booking, $actor, $reason): Booking {
            $booking->forceFill([
                'status' => BookingStatus::Declined->value,
                'payment_status' => PaymentStatus::Failed->value,
            ]);

            if ($reason !== null) {
                $booking->message_to_admin = trim(($booking->message_to_admin ?? '')."\n[declined] ".$reason);
            }

            $booking->save();

            $this->audit->record($actor, 'Booking Declined', "Booking ID: {$booking->getKey()} has been declined.");

            return $booking->refresh();
        });
    }

    /**
     * Customer-initiated cancellation. The row is kept so the customer keeps their
     * history; moving to the cancelled status is what frees the calendar.
     *
     * @throws BookingNotCancellableException
     */
    public function cancel(Booking $booking, User $actor): Booking
    {
        $status = $booking->bookingStatus();

        if ($status === null || ! $status->isCancellableByCustomer()) {
            throw new BookingNotCancellableException(
                'This booking can no longer be cancelled. Please contact us for assistance.'
            );
        }

        return DB::transaction(function () use ($booking, $actor): Booking {
            $booking->status = BookingStatus::Cancelled->value;
            $booking->save();

            $this->archive($booking, $actor->name ?? 'customer');

            return $booking->refresh();
        });
    }

    /** Admin-initiated void. Allowed from any status that still holds the calendar. */
    public function cancelAsAdmin(Booking $booking, User $actor, ?string $reason = null): Booking
    {
        return DB::transaction(function () use ($booking, $actor, $reason): Booking {
            $booking->status = BookingStatus::Cancelled->value;

            if ($reason !== null) {
                $booking->message_to_admin = trim(($booking->message_to_admin ?? '')."\n[cancelled] ".$reason);
            }

            $booking->save();

            $this->archive($booking, $actor->name ?? 'admin');

            return $booking->refresh();
        });
    }

    /**
     * Mirrors the booking into booking_history, which is what the admin
     * "cancelled bookings" screen reads.
     */
    private function archive(Booking $booking, string $actionBy): void
    {
        BookingHistory::updateOrCreate(
            ['booking_id' => $booking->getKey()],
            [
                'user_id' => $booking->user_id,
                'staycation_id' => $booking->staycation_id,
                'name' => $booking->name,
                'start_date' => $booking->start_date?->toDateString(),
                'end_date' => $booking->end_date?->toDateString(),
                'total_price' => $booking->total_price,
                'payment_status' => $booking->payment_status,
                'payment_proof' => $booking->payment_proof,
                'action_by' => $actionBy,
                'action_at' => now(),
                'deleted_at' => now(),
            ],
        );
    }
}
