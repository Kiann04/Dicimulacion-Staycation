<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\Staycation;
use App\Models\Booking;
use App\Mail\BookingCreated;
use Carbon\Carbon;

class BookingHistoryController extends Controller
{
    // 🏠 Show booking form for a selected staycation
    public function bookingForm($id)
    {
        $staycation = Staycation::findOrFail($id);
        return view('home.Booking', compact('staycation'));
    }

    // 📄 Step 1: Show Request-to-Book form (after clicking Reserve)
    public function requestToBook(Request $request, $id)
    {
        $staycation = Staycation::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'guest_number' => 'required|integer|min:1',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
        ]);

        $days = Carbon::parse($request->startDate)->diffInDays(Carbon::parse($request->endDate)) + 1;
        $totalPrice = $days * $staycation->house_price;
        $vatAmount = $totalPrice * 0.12;
        $totalWithVat = $totalPrice + $vatAmount;

        return view('home.RequestToBook', [
            'staycation' => $staycation,
            'name' => $request->name,
            'phone' => $request->phone,
            'guest_number' => $request->guest_number,
            'startDate' => $request->startDate,
            'endDate' => $request->endDate,
            'totalPrice' => $totalPrice,
            'vatAmount' => $vatAmount,
            'totalWithVat' => $totalWithVat,
        ]);
    }

    // 💳 Step 2: Handle final submission with proof of payment
    public function submitBooking(Request $request, $id)
    {
        $request->validate([
            'payment_option' => 'required|in:half_paid,paid',
            'payment_method' => 'required|string|max:50',
            'payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'message' => 'nullable|string|max:1000',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
        ]);

        $staycation = Staycation::findOrFail($id);
        $days = Carbon::parse($request->startDate)->diffInDays(Carbon::parse($request->endDate)) + 1;
        $totalPrice = $days * $staycation->house_price;
        $vatAmount = $totalPrice * 0.12;
        $totalWithVat = $totalPrice + $vatAmount;

        $user = Auth::user();

        // Upload payment proof
        $filePath = null;
        if ($request->hasFile('payment_proof')) {
            $file = $request->file('payment_proof');
            $fileName = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $filePath = 'uploads/payments/' . $fileName;
            $file->move(public_path('uploads/payments'), $fileName);
        }

        // Calculate paid amount if half or full
        $amountPaid = $request->payment_option === 'half_paid'
            ? $totalWithVat / 2
            : $totalWithVat;

        // Save booking
        $booking = Booking::create([
            'staycation_id' => $id,
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? $request->phone,
            'guest_number' => $request->guest_number,
            'start_date' => $request->startDate,
            'end_date' => $request->endDate,
            'total_price' => $totalWithVat,
            'vat_amount' => $vatAmount,
            'amount_paid' => $amountPaid,
            'payment_status' => $request->payment_option,
            'payment_proof' => $filePath,
            'status' => 'pending',
        ]);

        // Send booking confirmation email
        Mail::to($user->email)->send(new BookingCreated($booking));

        return redirect()->route('BookingHistory.index')
            ->with('success', 'Your booking request has been sent! Please wait for admin confirmation.');
    }

    // 📜 Show all bookings for logged-in user
    public function index()
    {
        $bookings = Booking::where('user_id', Auth::id())
            ->orderBy('start_date', 'desc')
            ->get();

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
    // 👀 Preview booking details before confirmation
    public function previewBooking(Request $request, $staycation_id)
    {
        $staycation = Staycation::findOrFail($staycation_id);

        $days = \Carbon\Carbon::parse($request->startDate)->diffInDays(\Carbon\Carbon::parse($request->endDate)) + 1;
        $totalPrice = $days * $staycation->house_price;

        return view('home.preview_booking', [
            'staycation' => $staycation,
            'name' => $request->name,
            'phone' => $request->phone,
            'guest_number' => $request->guest_number,
            'startDate' => $request->startDate,
            'endDate' => $request->endDate,
            'totalPrice' => $totalPrice
        ]);
    }
    // 🧾 Handle booking submission after preview page
    public function submitRequest(Request $request, $staycation_id)
    {
        $request->validate([
            'payment_type' => 'required|in:half,full',
            'payment_method' => 'required|in:gcash,bpi',
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'transaction_number' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:500',
        ]);

        $staycation = \App\Models\Staycation::findOrFail($staycation_id);

        // Compute prices
        $days = \Carbon\Carbon::parse($request->startDate)->diffInDays(\Carbon\Carbon::parse($request->endDate)) + 1;
        $totalPrice = $days * $staycation->house_price;
        $vat = $totalPrice * 0.12; // 12% VAT

        // Compute amount to pay (half or full)
        $amountPaid = $request->payment_type === 'half'
            ? $totalPrice * 0.5
            : $totalPrice;

        // Upload payment proof
        $proofPath = null;
        if ($request->hasFile('payment_proof')) {
            $proofPath = $request->file('payment_proof')->store('payment_proofs', 'public');
        }

        // Create booking record
        $booking = \App\Models\Booking::create([
            'staycation_id' => $staycation_id,
            'user_id' => auth()->id(),
            'name' => auth()->user()->name,
            'email' => auth()->user()->email,
            'phone' => auth()->user()->phone ?? null,
            'guest_number' => $request->guest_number ?? 1,
            'start_date' => $request->startDate,
            'end_date' => $request->endDate,
            'total_price' => $totalPrice,
            'price_per_day' => $staycation->house_price,
            'payment_status' => $request->payment_type === 'half' ? 'half_paid' : 'paid',
            'amount_paid' => $amountPaid,
            'vat_amount' => $vat,
            'payment_proof' => $proofPath,
            'status' => 'pending',
        ]);

        // Optional: send email notification (future use)
        // Mail::to(auth()->user()->email)->send(new BookingCreated($booking));

        return redirect()->route('BookingHistory.index')->with('success', 'Your booking request has been submitted! Wait for admin verification.');
    }


}
