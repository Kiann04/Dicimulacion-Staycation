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
            'endDate' => 'required|date|after:startDate',
        ]);

        $start = Carbon::parse($request->startDate);
        $end = Carbon::parse($request->endDate);

        // Correct number of nights (exclude departure day)
        $nights = $end->diffInDays($start);

        // Total price (before VAT, if you want VAT only in receipt)
        $totalPrice = $staycation->house_price * $nights;

        return view('home.preview_booking', [
            'staycation' => $staycation,
            'name' => $request->name,
            'phone' => $request->phone,
            'guest_number' => $request->guest_number,
            'startDate' => $request->startDate,
            'endDate' => $request->endDate,
            'nights' => $nights,
            'totalPrice' => $totalPrice,
        ]);
    }

    // ✅ Submit booking
    public function submitRequest(Request $request, $staycation_id)
    {
        $request->validate([
            'guest_number' => 'required|integer|min:1',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after:startDate',
            'payment_type' => 'required|in:half,full',
            'payment_method' => 'required|in:gcash,bpi',
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'transaction_number' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:500',
        ]);

        $staycation = Staycation::findOrFail($staycation_id);
        $start = Carbon::parse($request->startDate);
        $end = Carbon::parse($request->endDate);
        $nights = $end->diffInDays($start);

        $totalPrice = $staycation->house_price * $nights;

        // VAT only if you want to store it
        $vat = $totalPrice * 0.12;
        $totalWithVat = $totalPrice + $vat;

        $amountPaid = $request->payment_type === 'half' ? $totalPrice / 2 : $totalPrice;

        $proofPath = $request->file('payment_proof')->store('payment_proofs', 'public');

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
            'total_price' => $totalPrice, // store without adding extra days
            'vat_amount' => $vat,
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

    // Show booking history
    public function index()
    {
        $bookings = Booking::where('user_id', Auth::id())
                    ->orderBy('start_date', 'desc')
                    ->get();

        return view('home.Booking_History', compact('bookings'));
    }

    // Cancel pending booking
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
