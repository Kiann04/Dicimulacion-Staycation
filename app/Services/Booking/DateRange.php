<?php

namespace App\Services\Booking;

use App\Services\Booking\Exceptions\InvalidBookingDatesException;
use Carbon\CarbonImmutable;

/**
 * A stay expressed with half-open semantics: the guest occupies every night from
 * check-in up to but excluding check-out. This is what makes an existing stay
 * ending on the 10th compatible with a new stay starting on the 10th.
 */
final readonly class DateRange
{
    private function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
    ) {}

    public static function fromInput(string $start, string $end): self
    {
        try {
            $startDate = CarbonImmutable::parse($start)->startOfDay();
            $endDate = CarbonImmutable::parse($end)->startOfDay();
        } catch (\Throwable) {
            throw new InvalidBookingDatesException('The supplied dates could not be understood.');
        }

        if ($endDate->lessThanOrEqualTo($startDate)) {
            throw new InvalidBookingDatesException('The check-out date must be after the check-in date.');
        }

        return new self($startDate, $endDate);
    }

    public function nights(): int
    {
        return (int) $this->start->diffInDays($this->end);
    }

    public function startString(): string
    {
        return $this->start->toDateString();
    }

    public function endString(): string
    {
        return $this->end->toDateString();
    }

    public function isInThePast(): bool
    {
        return $this->start->lessThan(CarbonImmutable::today());
    }

    public function startsBeyond(int $days): bool
    {
        return $this->start->greaterThan(CarbonImmutable::today()->addDays($days));
    }
}
