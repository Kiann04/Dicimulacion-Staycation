<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Staycation;
use App\Models\Booking;
use App\Models\Review;
use Carbon\Carbon;

class BookingHistoryController extends Controller
{
    /**
     * 🏠 Show booking form for a selected staycation
     */
    public function bookingForm($id)
    {
        $staycation = Staycation::findOrFail($id);
        $reviews = Review::where('staycation_id', $id)->get();

        return view('home.RequestToBook', compact('staycation', 'reviews'));
    }

    /**
     * 📄 Submit booking request directly (no preview page)
     */
    public function submitRequest(Request $request, $staycation_id)
    {
        $staycation = Staycation::findOrFail($staycation_id);

        // ✅ Validation
        $request->validate([
            'guest_number' => 'required|integer|min:1',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
            'payment_type' => 'required|in:half,full',
            'payment_method' => 'required|in:gcash,bpi',
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'phone' => 'required|string|max:20',
            'transaction_number' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:500',
        ]);

        $startDate = Carbon::parse($request->startDate);
        $endDate = Carbon::parse($request->endDate);

        // 🧩 Prevent overlapping bookings
        $hasOverlap = Booking::where('staycation_id', $staycation_id)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where('start_date', '<', $endDate)
                    ->where('end_date', '>', $startDate);
            })
            ->exists();

        if ($hasOverlap) {
            return back()->with('error', '⚠️ The selected dates overlap with an existing booking. Please choose another range.');
        }

        // 🏨 Calculate total price
        $nights = max(1, $startDate->diffInDays($endDate));
        $totalPrice = $staycation->house_price * $nights;

        // Extra guests beyond 6 = ₱500 each
        $extraGuests = max(0, $request->guest_number - 6);
        $extraFee = $extraGuests * 500;
        $totalPrice += $extraFee;

        // Half or full payment amount
        $amountPaid = $request->payment_type === 'half'
            ? round($totalPrice / 2, 2)
            : $totalPrice;

        // 📸 Upload payment proof
        $proofPath = null;
        if ($request->hasFile('payment_proof')) {
            $proofFile = $request->file('payment_proof');
            $proofName = time() . '_' . $proofFile->getClientOriginalName();
            $proofFile->move(public_path('payment_proofs'), $proofName);
            $proofPath = 'payment_proofs/' . $proofName;
        }

        // ✅ Create booking record
        Booking::create([
            'staycation_id' => $staycation_id,
            'user_id' => Auth::id(),
            'name' => Auth::user()->name,
            'email' => Auth::user()->email,
            'phone' => $request->phone,
            'guest_number' => $request->guest_number,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'price_per_day' => $staycation->house_price,
            'total_price' => $totalPrice,
            'amount_paid' => $amountPaid,
            'payment_status' => 'unpaid',
            'payment_method' => $request->payment_method,
            'payment_proof' => $proofPath,
            'transaction_number' => $request->transaction_number ?? null,
            'message_to_admin' => $request->message ?? null,
            'status' => 'pending',
        ]);

        return redirect()->route('BookingHistory.index')
            ->with('success', '✅ Your booking has been submitted! Please wait for admin confirmation.');
    }

    /**
     * 📖 Show user's booking history
     */
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
