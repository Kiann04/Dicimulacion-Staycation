<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentInstructionsMail;
use App\Models\AuditLog;
use App\Mail\PaymentReceiptMail;
use Illuminate\Support\Facades\Auth;
use App\Mail\BookingApproved;
use App\Mail\BookingDeclined;

class AdminBookingController extends Controller
{
    // ✅ Approve booking (waiting for payment)
    public function approveBooking($id)
    {
        $booking = Booking::with('user', 'staycation')->findOrFail($id);

        $booking->status = 'approved';
        $booking->payment_status = 'pending'; 
        $booking->save();

        // Log action
        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => 'Booking Approved',
            'description'=> 'Booking ID: ' . $booking->id . ' approved and awaiting payment.',
            'ip_address' => request()->ip(),
        ]);

        // Send email
        $recipient = $booking->user->email ?? $booking->email;
        if (!empty($recipient)) {
            Mail::to($recipient)->send(new BookingApproved($booking));
        }

        return back()->with('success', 'Booking approved, audit log created, and email sent.');
    }

    // ✅ Decline booking → set status to Declined
    public function declineBooking($id)
    {
        $booking = Booking::with('user', 'staycation')->findOrFail($id);

        $booking->status = 'declined'; 
        $booking->payment_status = 'failed';
        $booking->save();

        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => 'Booking Declined',
            'description'=> 'Booking ID: ' . $booking->id . ' has been declined.',
            'ip_address' => request()->ip(),
        ]);

        if ($booking->user && $booking->user->email) {
            Mail::to($booking->user->email)->send(new BookingDeclined($booking));
        }

        return back()->with('success', 'Booking declined and email sent.');
    }


    // ✅ Update payment status
    public function updatePayment(Request $request, $id)
    {
        $booking = Booking::with('user', 'staycation')->findOrFail($id);
        $paymentStatus = strtolower($request->input('payment_status'));

        $booking->payment_status = $paymentStatus;

        // Define email recipient
        $recipient = $booking->user->email ?? $booking->email;

        // Both 'paid' and 'half_paid' should trigger the same email and confirm the booking
        if (in_array($paymentStatus, ['paid', 'half_paid'])) {
            $booking->status = 'confirmed';

            // Send email (same template for paid and half_paid)
            if (!empty($recipient)) {
                Mail::to($recipient)->send(new PaymentReceiptMail($booking));
            }

            AuditLog::create([
                'user_id'    => Auth::id(),
                'action'     => $paymentStatus === 'paid' ? 'Payment Received' : 'Partial Payment',
                'description'=> "Booking ID: {$booking->id} ({$booking->staycation->house_name}) marked as " . ucfirst(str_replace('_', ' ', $paymentStatus)) . ".",
                'ip_address' => request()->ip(),
            ]);
        } elseif ($paymentStatus === 'unpaid') {
            $booking->status = 'cancelled';

            AuditLog::create([
                'user_id'    => Auth::id(),
                'action'     => 'Payment Unpaid',
                'description'=> "Booking ID: {$booking->id} ({$booking->staycation->house_name}) marked as Unpaid.",
                'ip_address' => request()->ip(),
            ]);
        } else {
            $booking->status = 'approved';
        }

        $booking->save();

        return redirect()->back()->with('success', 'Payment status updated successfully!');
    }

}
