<?php

namespace App\Services\Booking;

/**
 * A server-computed price breakdown. The client never supplies money values;
 * it may only echo a quote back for display.
 */
final readonly class BookingQuote
{
    public function __construct(
        public DateRange $range,
        public int $nights,
        public int $guestNumber,
        public string $pricePerNight,
        public string $accommodationTotal,
        public int $extraGuests,
        public string $extraGuestFee,
        public string $totalPrice,
        public string $depositAmount,
        public string $balanceDue,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'start_date' => $this->range->startString(),
            'end_date' => $this->range->endString(),
            'nights' => $this->nights,
            'guest_number' => $this->guestNumber,
            'price_per_night' => $this->pricePerNight,
            'accommodation_total' => $this->accommodationTotal,
            'extra_guests' => $this->extraGuests,
            'extra_guest_fee' => $this->extraGuestFee,
            'total_price' => $this->totalPrice,
            'deposit_amount' => $this->depositAmount,
            'balance_due' => $this->balanceDue,
            'currency' => 'PHP',
        ];
    }

    /** Amount payable now for the chosen payment type. */
    public function amountDueFor(string $paymentType): string
    {
        return $paymentType === 'half' ? $this->depositAmount : $this->totalPrice;
    }
}
