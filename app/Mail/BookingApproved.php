<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingApproved extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    public function build()
    {
        return $this->subject('Booking Approved ✅')
                    ->markdown('emails.bookings.approved')
                    ->with([
                        'booking' => $this->booking,
                        'qrCodeUrl' => asset('images/QRPH.png'),
                        'bankName' => 'XYZ Bank',
                        'accountNo' => '123456789',
                        'accountName' => 'Staycation Rentals'
                    ]);
    }
}
