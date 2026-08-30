<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StaycationAvailabilityRequest;
use App\Http\Resources\Api\V1\StaycationAvailabilityResource;
use App\Models\Staycation;
use App\Services\BookingAvailabilityService;
use App\Services\BookingPricingService;

/**
 * Reports whether a date range can currently be booked.
 *
 * Every rule is read from BookingAvailabilityService, the Phase 1 authority, so
 * the API and the Blade booking form can never disagree about what is free. No
 * overlap query is written here.
 *
 * The lookup takes no lock and creates nothing. Booking creation in Phase 2B
 * re-checks the same rules inside the transaction that holds the staycation's
 * write lock, and that check — not this one — decides whether a stay happens.
 */
class StaycationAvailabilityController extends Controller
{
    /**
     * The property is closed to new bookings entirely.
     */
    public const REASON_PROPERTY_UNAVAILABLE = 'property_unavailable';

    /**
     * Another booking already holds a night in the range.
     */
    public const REASON_BOOKING_CONFLICT = 'booking_conflict';

    /**
     * An administrator has blocked a night in the range.
     */
    public const REASON_BLOCKED_DATES = 'blocked_dates';

    public function __construct(
        private readonly BookingAvailabilityService $availability,
        private readonly BookingPricingService $pricing,
    ) {}

    public function __invoke(
        StaycationAvailabilityRequest $request,
        Staycation $staycation,
    ): StaycationAvailabilityResource {
        $startDate = $request->startDate();
        $endDate = $request->endDate();
        $staycationId = (int) $staycation->getKey();

        $reasons = [];

        if (! $this->availability->isOpenForBooking($staycation)) {
            $reasons[] = self::REASON_PROPERTY_UNAVAILABLE;
        }

        if ($this->availability->hasBookingConflict($staycationId, $startDate, $endDate)) {
            $reasons[] = self::REASON_BOOKING_CONFLICT;
        }

        if ($this->availability->hasBlockedDateConflict($staycationId, $startDate, $endDate)) {
            $reasons[] = self::REASON_BLOCKED_DATES;
        }

        return new StaycationAvailabilityResource([
            'staycation_id' => $staycationId,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'nights' => $this->pricing->nights($startDate, $endDate),
            'available' => $reasons === [],
            'unavailable_reasons' => $reasons,
        ]);
    }
}
