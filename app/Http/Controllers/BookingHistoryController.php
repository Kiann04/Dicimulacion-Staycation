<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Staycation;
use App\Models\Booking;
use Carbon\Carbon;

class BookingHistoryController extends Controller
{
    // Show booking form
    public function bookingForm($id, Request $request)
    {   
        $staycation = Staycation::findOrFail($id);

        // Optional: get defaults from request or set fallback
        $guest_number = $request->guest_number ?? 1;
        $startDate = $request->startDate ?? now()->format('Y-m-d');
        $endDate = $request->endDate ?? now()->addDay()->format('Y-m-d');
        $phone = Auth::user()->phone ?? '';

        return view('home.RequestToBook', compact(
            'staycation',
            'guest_number',
            'startDate',
            'endDate',
            'phone'
        ));
    }


    // Submit booking request
    public function submitRequest(Request $request, $staycation_id)
    {
        $request->validate([
            'guest_number' => 'required|integer|min:1',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
            'phone' => 'required|string|max:20',
            'payment_type' => 'required|in:half,full',
            'payment_method' => 'required|in:gcash,bpi',
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'transaction_number' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:500',
        ]);

        $staycation = Staycation::findOrFail($staycation_id);

        $startDate = Carbon::parse($request->startDate);
        $endDate = Carbon::parse($request->endDate);

        $guest_number = (int)$request->guest_number;

        // Calculate nights (minimum 1)
        $nights = max(1, $startDate->diffInDays($endDate));

        // Base price
        $totalPrice = $staycation->house_price * $nights;

        // Extra guests beyond 6
        $extraGuests = max(0, $guest_number - 6);
        $extraFee = $extraGuests * 500;
        $totalPrice += $extraFee;

        // Amount to pay depending on payment_type
        $amountPaid = $request->payment_type === 'half'
            ? round($totalPrice / 2, 2)
            : $totalPrice;

        // Upload proof of payment
        $proofPath = null;
        if ($request->hasFile('payment_proof')) {
            $file = $request->file('payment_proof');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('payment_proofs'), $filename);
            $proofPath = 'payment_proofs/' . $filename;
        }

        // Prevent duplicate booking for same staycation & dates
        $duplicate = Booking::where('staycation_id', $staycation_id)
            ->where('start_date', $startDate->format('Y-m-d'))
            ->where('end_date', $endDate->format('Y-m-d'))
            ->first();

        if ($duplicate) {
            return back()->with('error', 'This staycation is already booked for the selected dates.');
        }

        // Create booking
        Booking::create([
            'staycation_id' => $staycation_id,
            'user_id' => Auth::id(),
            'name' => Auth::user()->name,
            'email' => Auth::user()->email,
            'phone' => $request->phone,
            'guest_number' => $guest_number,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'price_per_day' => $staycation->house_price,
            'total_price' => $totalPrice,
            'amount_paid' => $amountPaid,
            'payment_status' => 'unpaid',
            'payment_method' => $request->payment_method,
            'payment_proof' => $proofPath,
            'transaction_number' => $request->transaction_number,
            'message_to_admin' => $request->message,
            'status' => 'waiting',
        ]);

        return redirect()->route('BookingHistory.index')
            ->with('success', 'Your booking request has been submitted! Please wait for admin confirmation.');
    }

    public function index()
    {
        $bookings = Booking::where('user_id', Auth::id())
            ->orderBy('start_date', 'desc')
            ->get();

        return view('home.Booking_History', compact('bookings'));
    }

    /**
     * ❌ Cancel a pending booking
     */
    public function cancel($id)
    {
        $booking = Booking::where('id', $id)
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->firstOrFail();

        $booking->delete();

        return redirect()->route('BookingHistory.index')
            ->with('success', '🗑️ Booking cancelled successfully.');
    }

    /**
     * 🪙 Admin: Show fully paid bookings
     */
    public function showPaid()
    {
        $bookings = Booking::where('payment_status', 'paid')->latest()->get();
        $staycations = Staycation::all();
        return view('admin.paid_bookings', compact('bookings', 'staycations'))->with('filter', 'Paid Bookings');
    }

    /**
     * 💸 Admin: Show half-paid bookings
     */
    public function showHalfPaid()
    {
        $bookings = Booking::where('payment_status', 'half_paid')->latest()->get();
        $staycations = Staycation::all();
        return view('admin.half_paid_bookings', compact('bookings', 'staycations'))->with('filter', 'Half-Paid Bookings');
    }

    /**
     * 💰 Admin: Mark booking as paid
     */
    public function markAsPaid($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->payment_status = 'paid';
        $booking->save();

        return response()->json([
            'success' => true,
            'message' => '✅ Booking marked as fully paid!',
            'id' => $booking->id,
        ]);
    }
}
