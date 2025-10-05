<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Staycation;
use App\Models\Booking;
use Carbon\Carbon;

class BookingHistoryController extends Controller
{
    // 🏠 Show booking form for selected staycation
    public function bookingForm($id)
    {
        $staycation = Staycation::findOrFail($id);
        return view('home.Booking', compact('staycation'));
    }

    // 📄 Step 1: Preview Booking before confirming
    public function previewBooking(Request $request, $staycation_id)
    {
        $staycation = Staycation::findOrFail($staycation_id);

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'guest_number' => 'required|integer|min:1',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
        ]);

        // Correct calculation: number of nights
        $nights = Carbon::parse($request->startDate)->diffInDays(Carbon::parse($request->endDate));
        $totalPrice = $nights * $staycation->house_price;

        return view('home.preview_booking', [
            'staycation' => $staycation,
            'name' => $request->name,
            'phone' => $request->phone,
            'guest_number' => $request->guest_number,
            'startDate' => $request->startDate,
            'endDate' => $request->endDate,
            'totalPrice' => $totalPrice,
        ]);
    }

    // 📄 Step 2: Submit booking request
    public function submitRequest(Request $request, $staycation_id)
{
    $request->validate([
        'guest_number' => 'required|integer|min:1',
        'startDate' => 'required|date',
        'endDate' => 'required|date|after:startDate', // must be after startDate
        'payment_type' => 'required|in:half,full',
        'payment_method' => 'required|in:gcash,bpi',
        'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        'transaction_number' => 'nullable|string|max:255',
        'message' => 'nullable|string|max:500',
    ]);

    $staycation = Staycation::findOrFail($staycation_id);

    // Parse dates
    $start = Carbon::parse($request->startDate);
    $end = Carbon::parse($request->endDate);

    // Number of nights (exclude departure)
    $nights = $end->diffInDays($start); // **do not add +1**
    $totalPrice = $nights * $staycation->house_price;

    // Payment calculation
    $amountPaid = $request->payment_type === 'half' ? $totalPrice / 2 : $totalPrice;

    // Upload proof of payment
    $proofPath = $request->file('payment_proof')->store('payment_proofs', 'public');

    // Create booking
    Booking::create([
        'staycation_id' => $staycation_id,
        'user_id' => Auth::id(),
        'name' => Auth::user()->name,
        'email' => Auth::user()->email,
        'phone' => Auth::user()->phone ?? $request->phone,
        'guest_number' => $request->guest_number,
        'start_date' => $request->startDate,
        'end_date' => $request->endDate,
        'price_per_day' => $staycation->house_price,
        'total_price' => $totalPrice, // now matches per-night calculation
        'amount_paid' => $amountPaid,
        'payment_status' => $request->payment_type === 'half' ? 'half_paid' : 'paid',
        'payment_method' => $request->payment_method,
        'payment_proof' => $proofPath,
        'transaction_number' => $request->transaction_number,
        'message_to_admin' => $request->message,
        'status' => 'pending',
    ]);

    return redirect()->route('BookingHistory.index')
                     ->with('success', 'Your booking request has been submitted! Wait for admin confirmation.');
}



    // 📖 Show booking history
    public function index()
    {
        $bookings = Booking::where('user_id', Auth::id())->orderBy('start_date', 'desc')->get();
        return view('home.Booking_History', compact('bookings'));
    }

    // ❌ Cancel pending booking
    public function cancel($id)
    {
        $booking = Booking::where('id', $id)
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->firstOrFail();

        $booking->delete();

        return redirect()->route('BookingHistory.index')
            ->with('success', 'Booking cancelled successfully.');
    }
}
