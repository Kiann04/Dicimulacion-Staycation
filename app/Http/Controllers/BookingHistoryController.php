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

    // ➕ Handle booking submission
    public function addBooking(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'guest_number' => 'required|integer|min:1',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
            'terms' => 'required'
        ]);

        $startDate = $request->startDate;
        $endDate = $request->endDate;

        // Check if dates overlap with existing bookings
        $isBooked = Booking::where('staycation_id', $id)
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->exists();

        if ($isBooked) {
            return redirect()->back()->with('message', 'Sorry, these dates are already booked.');
        }

        $staycation = Staycation::findOrFail($id);
        $days = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        $totalPrice = $days * $staycation->house_price;

        // Create booking
        $booking = Booking::create([
            'staycation_id' => $id,
            'user_id' => Auth::id(),
            'name' => $request->name,
            'phone' => $request->phone,
            'guest_number' => $request->guest_number,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_price' => $totalPrice,
            'status' => 'pending'
        ]);

        // Send email confirmation
        if (Auth::check()) {
            Mail::to(Auth::user()->email)->send(new BookingCreated($booking));
        }

        return redirect()->back()->with('success', 'Booking successfully added! Wait for admin approval.');
    }

    // 👀 Preview booking details before confirmation
    public function previewBooking(Request $request, $staycation_id)
    {
        $staycation = Staycation::findOrFail($staycation_id);
        $days = Carbon::parse($request->startDate)->diffInDays(Carbon::parse($request->endDate)) + 1;
        $totalPrice = $days * $staycation->house_price;

        return view('home.booking_preview', [
            'staycation' => $staycation,
            'name' => $request->name,
            'phone' => $request->phone,
            'guest_number' => $request->guest_number,
            'startDate' => $request->startDate,
            'endDate' => $request->endDate,
            'totalPrice' => $totalPrice
        ]);
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
}
