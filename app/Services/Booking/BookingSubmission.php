<?php

namespace App\Services\Booking;

use Illuminate\Http\UploadedFile;

/**
 * Everything a customer is allowed to supply when submitting a booking.
 * Note the absence of any money field - pricing is derived server-side.
 */
final readonly class BookingSubmission
{
    public function __construct(
        public DateRange $range,
        public int $guestNumber,
        public string $phone,
        public string $paymentType,
        public string $paymentMethod,
        public ?UploadedFile $paymentProof = null,
        public ?string $transactionNumber = null,
        public ?string $messageToAdmin = null,
    ) {}
}
