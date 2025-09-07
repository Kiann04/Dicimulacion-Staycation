<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentInstructionsMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;

    public function __construct($booking)
    {
        $this->booking = $booking;
    }

    public function build()
    {
        return $this->subject('Your Booking Payment Instructions')
                    ->view('emails.payment_instructions')
                    ->with([
                        'booking' => $this->booking,
                        'payment_method' => 'Bank Transfer - BDO Account 1234-5678-90', // Example
                    ]);
    }
}
