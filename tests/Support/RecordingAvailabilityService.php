<?php

namespace Tests\Support;

use App\Models\Staycation;
use App\Services\BookingAvailabilityService;
use Carbon\CarbonImmutable;

/**
 * Records the order in which an inventory write consults availability.
 *
 * The staycation row lock is what serializes concurrent writes, so what matters
 * is that it is taken before anything is read and before anything is written.
 */
class RecordingAvailabilityService extends BookingAvailabilityService
{
    /** @var array<int, string> */
    public array $calls = [];

    public function lockStaycation(int $staycationId): Staycation
    {
        $this->calls[] = 'lock';

        return parent::lockStaycation($staycationId);
    }

    public function isOpenForBooking(Staycation $staycation): bool
    {
        $this->calls[] = 'open-for-booking';

        return parent::isOpenForBooking($staycation);
    }

    public function hasBookingConflict(
        int $staycationId,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?int $ignoreBookingId = null,
    ): bool {
        $this->calls[] = 'booking-conflict';

        return parent::hasBookingConflict($staycationId, $startDate, $endDate, $ignoreBookingId);
    }

    public function hasBlockedDateConflict(
        int $staycationId,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?int $ignoreBlockedDateId = null,
    ): bool {
        $this->calls[] = 'blocked-date-conflict';

        return parent::hasBlockedDateConflict($staycationId, $startDate, $endDate, $ignoreBlockedDateId);
    }
}
