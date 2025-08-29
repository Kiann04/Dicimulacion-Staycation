<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InquiryReply extends Mailable
{
    use Queueable, SerializesModels;
    public $messageBody;
    public $inquiry;
    /**
     * Create a new message instance.
     */
    public function __construct($messageBody, $inquiry)
    {
        $this->messageBody = $messageBody;
        $this->inquiry = $inquiry;
    }
    public function build()
    {
        return $this->subject('Reply to your inquiry')
                    ->view('emails.inquiry_reply');
    }
}
