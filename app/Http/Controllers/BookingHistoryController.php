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
    // 🏠 Step 0: Show booking form
    public function bookingForm($id)
    {
        $staycation = Staycation::findOrFail($id);
        $reviews = Review::where('staycation_id', $id)->get();

        return view('home.Booking', compact('staycation', 'reviews'));
    }

    // 📝 Step 1: Preview booking — POST
    public function previewBookingPost(Request $request, $staycation_id)
    {
        $staycation = Staycation::findOrFail($staycation_id);

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'guest_number' => 'required|integer|min:1',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after:startDate',
        ]);

        $startDate = Carbon::parse($request->startDate);
        $endDate = Carbon::parse($request->endDate);

        // 🧭 Overlap check
        $hasOverlap = Booking::where('staycation_id', $staycation->id)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where('start_date', '<', $endDate)
                      ->where('end_date', '>', $startDate);
            })
            ->exists();

        if ($hasOverlap) {
            return back()->with('message', '⚠️ The selected dates overlap with an existing booking. Please choose another range.');
        }

        $nights = $startDate->diffInDays($endDate);
        $totalPrice = $nights * $staycation->house_price;

        // 🧠 Store preview data in session
        session([
            'preview' => [
                'staycation_id' => $staycation_id,
                'name' => $request->name,
                'phone' => $request->phone,
                'guest_number' => $request->guest_number,
                'startDate' => $request->startDate,
                'endDate' => $request->endDate,
                'totalPrice' => $totalPrice,
            ]
        ]);

        return redirect()->route('booking.preview.get', $staycation_id);
    }

    // 👀 Step 1 (GET): Show preview page
    public function previewBookingGet($staycation_id)
    {
        $staycation = Staycation::findOrFail($staycation_id);
        $preview = session('preview');

        // Redirect if user tries to access preview without posting data
        if (!$preview || $preview['staycation_id'] != $staycation_id) {
            return redirect()->route('booking.form', $staycation_id)
                             ->with('message', '⚠️ Please fill out the booking form first.');
        }

        return view('home.preview_booking', [
            'staycation' => $staycation,
            'name' => $preview['name'],
            'phone' => $preview['phone'],
            'guest_number' => $preview['guest_number'],
            'startDate' => $preview['startDate'],
            'endDate' => $preview['endDate'],
            'totalPrice' => $preview['totalPrice'],
        ]);
    }

    // 📩 Step 2: Submit booking request
    public function submitRequest(Request $request, $staycation_id)
    {
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

        $staycation = Staycation::findOrFail($staycation_id);

        $start = Carbon::parse($request->startDate);
        $end = Carbon::parse($request->endDate);

        $nights = $end->lessThanOrEqualTo($start) ? 1 : $start->diffInDays($end);
        $totalPrice = $staycation->house_price * $nights;

        $extraGuests = max(0, $request->guest_number - 6);
        $extraFee = $extraGuests * 500;
        $totalPrice += $extraFee;

        $amountPaid = $request->payment_type === 'half' ? round($totalPrice / 2, 2) : $totalPrice;

        $proofPath = null;
        if ($request->hasFile('payment_proof')) {
            $proofFile = $request->file('payment_proof');
            $proofName = time().'_'.$proofFile->getClientOriginalName();
            $proofFile->move(public_path('payment_proofs'), $proofName);
            $proofPath = 'payment_proofs/'.$proofName;
        }

        // 🛡 Prevent double booking
        $duplicate = Booking::where('staycation_id', $staycation_id)
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end])
                      ->orWhereBetween('end_date', [$start, $end])
                      ->orWhere(function ($q) use ($start, $end) {
                          $q->where('start_date', '<=', $start)
                            ->where('end_date', '>=', $end);
                      });
            })
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($duplicate) {
            return back()->with('error', 'This staycation is already booked for the selected dates.');
        }

        Booking::create([
            'staycation_id' => $staycation_id,
            'user_id' => Auth::id(),
            'name' => Auth::user()->name,
            'email' => Auth::user()->email,
            'phone' => $request->phone,
            'guest_number' => $request->guest_number,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
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

        // Clear preview session after successful booking
        session()->forget('preview');

        return redirect()->route('BookingHistory.index')
            ->with('success', 'Your booking has been submitted! Please wait for admin confirmation.');
    }

    // 📖 Booking History
    public function index()
    {
        $bookings = Booking::where('user_id', Auth::id())
            ->orderBy('start_date', 'desc')
            ->get();

        return view('home.Booking_History', compact('bookings'));
    }

    // ❌ Cancel Booking
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

    // 💰 Admin Side: Paid Bookings
    public function showPaid()
    {
        $bookings = Booking::where('payment_status', 'paid')->latest()->get();
        $staycations = Staycation::all();
        return view('admin.paid_bookings', compact('bookings', 'staycations'))->with('filter', 'Paid Bookings');
    }

    // 💵 Admin Side: Half Paid
    public function showHalfPaid()
    {
        $bookings = Booking::where('payment_status', 'half_paid')->latest()->get();
        $staycations = Staycation::all();
        return view('admin.half_paid_bookings', compact('bookings', 'staycations'))->with('filter', 'Half-Paid Bookings');
    }

    public function markAsPaid($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->payment_status = 'paid';
        $booking->save();

        return response()->json([
            'success' => true,
            'message' => 'Booking marked as fully paid!',
            'id' => $booking->id,
        ]);
    }
}
