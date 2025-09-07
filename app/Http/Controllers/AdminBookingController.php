<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentInstructionsMail;

class AdminBookingController extends Controller
{
    public function approve($id)
    {
        $booking = Booking::with('staycation')->findOrFail($id);

        // Update status
        $booking->status = 'approved';
        $booking->save();

        // Send email
        Mail::to($booking->email)->send(new PaymentInstructionsMail($booking));

        return redirect()->back()->with('success', 'Booking approved and payment instructions sent.');
    }
}
