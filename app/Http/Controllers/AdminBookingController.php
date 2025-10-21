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
use Illuminate\Support\Facades\DB;

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

    $recipient = $booking->user->email ?? $booking->email;

    if (in_array($paymentStatus, ['paid', 'half_paid'])) {
        $booking->status = 'confirmed';

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

    // ✅ Detect AJAX and respond accordingly
    if ($request->ajax()) {
        return response()->json([
            'success' => true,
            'message' => 'Payment status updated successfully!',
            'booking_status' => $booking->status,
        ]);
    }

    return redirect()->back()->with('success', 'Payment status updated successfully!');
}


    public function getUnpaidCount()
    {
        $count = \App\Models\Booking::where('payment_status', 'unpaid')->count();
        return response()->json(['count' => $count]);
    }
    public function getProof($id)
    {
        $booking = DB::table('bookings')->where('id', $id)->first();

        return response()->json([
            'proof' => $booking && $booking->payment_proof
                ? asset($booking->payment_proof)
                : null
        ]);
    }




}
