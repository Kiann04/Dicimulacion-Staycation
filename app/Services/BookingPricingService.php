<?php

namespace App\Services;

use App\Models\Staycation;
use App\Support\Money;
use Carbon\CarbonImmutable;

/**
 * The single authority on what a booking costs.
 *
 * Rule: nightly rate x nights, plus a flat fee for every guest beyond the
 * included headcount. Totals submitted by the client are never trusted.
 *
 * Every figure is a Money (integer centavos). A half payment rounds HALF_UP to
 * the centavo, so two halves can never settle for less than the total.
 */
class BookingPricingService
{
    /**
     * Guests included in the nightly rate before extra-guest fees apply.
     */
    public const INCLUDED_GUESTS = 6;

    /**
     * Flat fee, in pesos, per guest beyond the included headcount.
     */
    public const EXTRA_GUEST_FEE = 500;

    /**
     * Maximum guests a staycation accepts.
     */
    public const MAXIMUM_GUESTS = 8;

    /**
     * Price a stay.
     *
     * @return array{nights: int, price_per_day: Money, base_price: Money, extra_guests: int, extra_guest_fee: Money, total_price: Money}
     */
    public function quote(
        Staycation $staycation,
        int $guestNumber,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): array {
        $nights = $this->nights($startDate, $endDate);
        $pricePerDay = Money::fromDecimal($staycation->house_price);
        $basePrice = $pricePerDay->multipliedBy($nights);

        $extraGuests = max(0, $guestNumber - self::INCLUDED_GUESTS);
        $extraGuestFee = Money::fromDecimal(self::EXTRA_GUEST_FEE)->multipliedBy($extraGuests);

        return [
            'nights' => $nights,
            'price_per_day' => $pricePerDay,
            'base_price' => $basePrice,
            'extra_guests' => $extraGuests,
            'extra_guest_fee' => $extraGuestFee,
            'total_price' => $basePrice->plus($extraGuestFee),
        ];
    }

    /**
     * Nights between two dates, never fewer than one.
     */
    public function nights(CarbonImmutable $startDate, CarbonImmutable $endDate): int
    {
        if ($endDate->lessThanOrEqualTo($startDate)) {
            return 1;
        }

        return (int) $startDate->diffInDays($endDate);
    }

    /**
     * The amount due up front for the chosen payment type.
     */
    public function amountDue(Money $totalPrice, string $paymentType): Money
    {
        return $paymentType === 'half'
            ? $totalPrice->half()
            : $totalPrice;
    }

    /**
     * What is still owed on a booking.
     */
    public function remainingBalance(Money $totalPrice, ?Money $amountPaid): Money
    {
        return $totalPrice->minusOrZero($amountPaid ?? Money::zero());
    }
}
