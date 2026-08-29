<?php

namespace App\Services\Booking;

use App\Models\Staycation;
use App\Services\Booking\Exceptions\GuestCapacityExceededException;
use App\Services\Booking\Exceptions\InvalidBookingDatesException;

/**
 * Authoritative pricing. Every money figure the application stores or displays
 * originates here; nothing supplied by a client is ever trusted as a price.
 *
 * Amounts are computed with bcmath and returned as decimal strings so that
 * repeated halving and summing cannot drift the way binary floats do.
 */
class BookingPricingService
{
    private const SCALE = 2;

    /**
     * @throws GuestCapacityExceededException
     * @throws InvalidBookingDatesException
     */
    public function quote(Staycation $staycation, DateRange $range, int $guestNumber): BookingQuote
    {
        $this->assertGuestCountAllowed($guestNumber);
        $this->assertStayLengthAllowed($range);

        $nights = $range->nights();
        $pricePerNight = $this->normalise((string) $staycation->house_price);

        $accommodationTotal = bcmul($pricePerNight, (string) $nights, self::SCALE);

        $extraGuests = max(0, $guestNumber - (int) config('booking.free_guest_threshold'));
        $extraGuestFee = bcmul(
            $this->normalise((string) config('booking.extra_guest_fee')),
            (string) $extraGuests,
            self::SCALE,
        );

        $totalPrice = bcadd($accommodationTotal, $extraGuestFee, self::SCALE);

        $depositAmount = $this->deposit($totalPrice);

        return new BookingQuote(
            range: $range,
            nights: $nights,
            guestNumber: $guestNumber,
            pricePerNight: $pricePerNight,
            accommodationTotal: $accommodationTotal,
            extraGuests: $extraGuests,
            extraGuestFee: $extraGuestFee,
            totalPrice: $totalPrice,
            depositAmount: $depositAmount,
            balanceDue: bcsub($totalPrice, $depositAmount, self::SCALE),
        );
    }

    /**
     * The deposit is rounded to the cent so deposit + balance always reconstructs
     * the total exactly, with any half-cent falling to the deposit.
     */
    public function deposit(string $totalPrice): string
    {
        $ratio = $this->normalise((string) config('booking.deposit_ratio'), 4);

        return $this->roundHalfUp(bcmul($totalPrice, $ratio, 6));
    }

    /**
     * @throws GuestCapacityExceededException
     */
    public function assertGuestCountAllowed(int $guestNumber): void
    {
        $maxGuests = (int) config('booking.max_guests');

        if ($guestNumber < 1) {
            throw new GuestCapacityExceededException('At least one guest is required.');
        }

        if ($guestNumber > $maxGuests) {
            throw new GuestCapacityExceededException(
                "This staycation accommodates a maximum of {$maxGuests} guests."
            );
        }
    }

    /**
     * @throws InvalidBookingDatesException
     */
    public function assertStayLengthAllowed(DateRange $range): void
    {
        $nights = $range->nights();
        $minNights = (int) config('booking.min_nights');
        $maxNights = (int) config('booking.max_nights');

        if ($nights < $minNights) {
            throw new InvalidBookingDatesException("A stay must be at least {$minNights} night(s).");
        }

        if ($nights > $maxNights) {
            throw new InvalidBookingDatesException("A stay may not exceed {$maxNights} nights.");
        }

        if ($range->isInThePast()) {
            throw new InvalidBookingDatesException('The check-in date cannot be in the past.');
        }

        $maxAdvanceDays = (int) config('booking.max_advance_days');

        if ($range->startsBeyond($maxAdvanceDays)) {
            throw new InvalidBookingDatesException("Bookings can only be made up to {$maxAdvanceDays} days in advance.");
        }
    }

    private function roundHalfUp(string $value): string
    {
        $increment = bccomp($value, '0', 6) >= 0 ? '0.005' : '-0.005';

        return bcadd($value, $increment, self::SCALE);
    }

    private function normalise(string $value, int $scale = self::SCALE): string
    {
        return bcadd($value === '' ? '0' : $value, '0', $scale);
    }
}
