<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\InvalidBookingTransition;
use App\Exceptions\PaymentAdjustmentRequired;
use App\Exceptions\StaycationUnavailable;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Staycation;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Every write that changes what is bookable for a staycation.
 *
 * LOCKING CONVENTION
 * ------------------
 * Every path in this service, and in BookingPaymentService, acquires locks in
 * the same order inside one transaction:
 *
 *     1. staycation row(s), ascending id
 *     2. booking row
 *
 * Taking them in a fixed order is what stops two requests touching the same pair
 * of rows in opposite orders from deadlocking. A move between two properties
 * therefore locks both, smallest id first.
 *
 *     BEGIN
 *       SELECT ... FROM staycations WHERE id = ? FOR UPDATE   <- the mutex
 *       SELECT ... FROM bookings    WHERE id = ? FOR UPDATE   <- when editing one
 *       check the property is open for booking
 *       check booking overlaps
 *       check blocked-date overlaps
 *       check the price may still change
 *       write
 *     COMMIT
 *
 * Locking the staycation's own primary-key row is what makes check-then-write
 * atomic. Locking only the overlap query would leave two simultaneous requests
 * both finding an empty result and both inserting, since an empty range has no
 * row to lock; that falls back to InnoDB gap locking, which depends on the
 * isolation level and is not something to rely on.
 *
 * WHICH OPERATIONS NEED WHICH LOCK
 * --------------------------------
 *   booking creation        staycation — it takes inventory
 *   admin reschedule        staycation(s) + booking — takes inventory and edits a row
 *   blocked-date creation   staycation — it takes inventory
 *   availability toggle     staycation — closing a property races with creation
 *   cancel / decline        booking only — these only *release* inventory, which
 *                           cannot cause a double booking; what they do need is a
 *                           serialized state transition, so BookingPaymentService
 *                           locks the booking row
 *   archive + hard delete   booking only — same reasoning, plus atomicity
 *
 * Deadlocks are handled by Laravel's own transaction retry rather than any
 * bespoke retry code.
 */
class BookingInventoryService
{
    /**
     * How many times a transaction is retried when the database reports a
     * deadlock. Laravel re-runs the whole closure, which re-reads state, so the
     * retry is safe.
     */
    private const DEADLOCK_ATTEMPTS = 3;

    /**
     * Fields a caller may never set through an inventory operation. Money and
     * payment state belong to BookingPaymentService alone.
     *
     * @var array<int, string>
     */
    private const FINANCIAL_FIELDS = [
        'status',
        'payment_status',
        'amount_paid',
        'declared_amount',
        'payment_type',
        'total_price',
        'price_per_day',
    ];

    public function __construct(
        private readonly BookingAvailabilityService $availability,
        private readonly BookingPricingService $pricing,
    ) {}

    /**
     * Run a callback while holding a staycation's write lock.
     *
     * @template TReturn
     *
     * @param  callable(Staycation): TReturn  $callback
     * @return TReturn
     */
    public function withStaycationLock(int $staycationId, callable $callback): mixed
    {
        return DB::transaction(
            fn () => $callback($this->availability->lockStaycation($staycationId)),
            self::DEADLOCK_ATTEMPTS,
        );
    }

    /**
     * Create a booking, pricing it and re-checking availability under the lock.
     *
     * @param  array<string, mixed>  $details
     *
     * @throws StaycationUnavailable
     */
    public function createBooking(
        int $staycationId,
        User $user,
        int $guestNumber,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        array $details,
    ): Booking {
        return $this->withStaycationLock($staycationId, function (Staycation $staycation) use (
            $user,
            $guestNumber,
            $startDate,
            $endDate,
            $details
        ): Booking {
            $this->assertRangeIsTakeable($staycation, $startDate, $endDate);

            $quote = $this->pricing->quote($staycation, $guestNumber, $startDate, $endDate);
            $declared = $this->pricing->amountDue($quote['total_price'], $details['payment_type']);

            return Booking::create([
                'staycation_id' => $staycation->getKey(),
                'user_id' => $user->getKey(),
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $details['phone'],
                'guest_number' => $guestNumber,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'price_per_day' => $quote['price_per_day']->toDecimalString(),
                'total_price' => $quote['total_price']->toDecimalString(),

                // Nobody has checked the proof yet, so nothing is verified.
                'amount_paid' => Money::zero()->toDecimalString(),
                'declared_amount' => $declared->toDecimalString(),
                'payment_type' => $details['payment_type'],
                'payment_status' => PaymentStatus::Pending->value,

                'payment_method' => $details['payment_method'],
                'payment_proof' => $details['payment_proof'],
                'transaction_number' => $details['transaction_number'] ?? null,
                'message_to_admin' => $details['message'] ?? null,
                'status' => BookingStatus::Pending->value,
            ]);
        });
    }

    /**
     * Move a booking's dates, staycation or guest count, repricing it.
     *
     * Locks the staycations involved (ascending id, so a move between two
     * properties cannot deadlock against a move in the opposite direction) and
     * then the booking row, matching the convention in the class docblock.
     *
     * Once money has been verified as received, the price is frozen: repricing a
     * paid booking would silently invent an unrecorded balance or refund, so the
     * change is refused and handed to a human.
     *
     * @param  array<string, mixed>  $details  non-financial fields only
     *
     * @throws StaycationUnavailable
     * @throws PaymentAdjustmentRequired
     */
    public function rescheduleBooking(
        Booking $booking,
        int $staycationId,
        int $guestNumber,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        array $details = [],
    ): Booking {
        $bookingId = $booking->getKey();

        $updated = DB::transaction(function () use (
            $bookingId,
            $booking,
            $staycationId,
            $guestNumber,
            $startDate,
            $endDate,
            $details
        ): Booking {
            $staycations = $this->lockStaycations([$booking->staycation_id, $staycationId]);
            $staycation = $staycations[$staycationId];

            $locked = Booking::query()->lockForUpdate()->findOrFail($bookingId);

            $this->assertReschedulable($locked);

            $this->assertRangeIsTakeable($staycation, $startDate, $endDate, $locked->getKey());

            $quote = $this->pricing->quote($staycation, $guestNumber, $startDate, $endDate);

            $this->assertRepricingIsAllowed($locked, $quote['total_price']);

            $locked->update(array_merge(
                Arr::except($details, self::FINANCIAL_FIELDS),
                [
                    'staycation_id' => $staycation->getKey(),
                    'guest_number' => $guestNumber,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'price_per_day' => $quote['price_per_day']->toDecimalString(),
                    'total_price' => $quote['total_price']->toDecimalString(),
                ]
            ));

            return $locked;
        }, self::DEADLOCK_ATTEMPTS);

        $booking->setRawAttributes($updated->getAttributes(), true);

        return $updated;
    }

    /**
     * Lock the given staycations in ascending id order.
     *
     * Ordering is what prevents two rescheduling requests moving bookings in
     * opposite directions between the same pair of properties from deadlocking.
     *
     * The *source* id comes from the caller's model, which is read before the
     * booking row is locked and could in principle be stale. That is deliberate
     * and not a correctness gap: the target staycation — the one whose
     * availability decides whether the write may happen — is always locked
     * correctly, and the source lock only serializes the *release* of the old
     * dates, which cannot cause a double booking. Reading the source under the
     * booking lock instead would mean taking a booking lock before a staycation
     * lock, inverting the order every other path uses, so the stale read is the
     * cheaper trade. See BookingConcurrencyTest for the behaviour under a
     * concurrent move.
     *
     * @param  array<int, int|null>  $staycationIds
     * @return array<int, Staycation>
     */
    private function lockStaycations(array $staycationIds): array
    {
        $ids = collect($staycationIds)->filter()->unique()->sort()->values();

        $locked = [];

        foreach ($ids as $id) {
            $staycation = $this->availability->lockStaycation((int) $id);
            $locked[$staycation->getKey()] = $staycation;
        }

        return $locked;
    }

    /**
     * A finished booking is not rescheduled, it is left alone.
     *
     * Moving a cancelled, declined or completed stay to new dates would take
     * inventory back for a booking nobody is going to honour. Reviving one is a
     * decision that needs its own flow, not a side effect of an edit form.
     *
     * @throws InvalidBookingTransition
     */
    private function assertReschedulable(Booking $booking): void
    {
        $status = $booking->bookingStatus()
            ?? throw InvalidBookingTransition::forUnknownBookingStatus($booking->status);

        if ($status->isTerminal()) {
            throw InvalidBookingTransition::forTerminalBooking($status, 'rescheduled');
        }
    }

    /**
     * A booking whose money has been verified may not change price.
     *
     * @throws PaymentAdjustmentRequired
     */
    private function assertRepricingIsAllowed(Booking $booking, Money $proposedTotal): void
    {
        if (! $booking->paymentStatus()?->isVerified()) {
            return;
        }

        $currentTotal = $booking->totalPrice();

        if (! $proposedTotal->equals($currentTotal)) {
            throw PaymentAdjustmentRequired::priceWouldChange($currentTotal, $proposedTotal);
        }
    }

    /**
     * Block a date range, refusing to strand an active booking inside it.
     *
     * @throws StaycationUnavailable
     */
    public function createBlockedDate(
        int $staycationId,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?string $reason,
    ): BlockedDate {
        return $this->withStaycationLock($staycationId, function (Staycation $staycation) use (
            $startDate,
            $endDate,
            $reason
        ): BlockedDate {
            $hasActiveBooking = $this->availability
                ->bookingsWithinBlockedRange($staycation->getKey(), $startDate, $endDate)
                ->exists();

            if ($hasActiveBooking) {
                throw StaycationUnavailable::blockedRangeHasBookings($staycation->house_name);
            }

            return BlockedDate::create([
                'staycation_id' => $staycation->getKey(),
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'reason' => $reason,
            ]);
        });
    }

    /**
     * The property is open, and the range is free of bookings and blocks.
     *
     * @throws StaycationUnavailable
     */
    private function assertRangeIsTakeable(
        Staycation $staycation,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?int $ignoreBookingId = null,
    ): void {
        if (! $this->availability->isOpenForBooking($staycation)) {
            throw StaycationUnavailable::notOpenForBooking($staycation->house_name);
        }

        if ($this->availability->hasBookingConflict($staycation->getKey(), $startDate, $endDate, $ignoreBookingId)) {
            throw StaycationUnavailable::datesTaken($staycation->house_name);
        }

        if ($this->availability->hasBlockedDateConflict($staycation->getKey(), $startDate, $endDate)) {
            throw StaycationUnavailable::datesBlocked($staycation->house_name);
        }
    }
}
