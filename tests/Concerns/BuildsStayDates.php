<?php

namespace Tests\Concerns;

use Carbon\CarbonImmutable;

/**
 * Stay dates expressed as offsets from today.
 *
 * Customer-facing booking rules reject arrival dates in the past, so fixtures
 * must not hard-code calendar dates: a suite written with literal 2026 dates
 * starts failing the moment 2026 becomes the past.
 *
 * `day(10)` means "ten days from today", in the application's timezone.
 */
trait BuildsStayDates
{
    protected function day(int $offsetFromToday): string
    {
        return $this->dayAsCarbon($offsetFromToday)->toDateString();
    }

    protected function dayAsCarbon(int $offsetFromToday): CarbonImmutable
    {
        return CarbonImmutable::today()->addDays($offsetFromToday);
    }
}
