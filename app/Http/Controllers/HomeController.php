<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Staycation;
use App\Models\Booking;
use App\Models\Review;
use Carbon\Carbon;
use App\Mail\BookingCreated;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    // Homepage with available staycations and latest reviews
    public function index()
    {
        $staycations = Staycation::where('house_availability', 'available')->get();
        $reviews = Review::with('user')->latest()->get();

        return view('home.Homepage', compact('staycations', 'reviews'));
    }

    // Show booking form
    public function bookingForm($id)
    {
        $staycation = Staycation::findOrFail($id);
        return view('home.Booking', compact('staycation'));
    }

    // Handle booking submission
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

        // Check if dates are already booked
        $isBooked = Booking::where('staycation_id', $id)
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->exists();

        if ($isBooked) {
            return redirect()->back()->with(
    'success',
    "Booking successfully added! Wait for admin approval.\nPlease check your email for confirmation."
);

        }

        $staycation = Staycation::findOrFail($id);
        $days = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        $totalPrice = $days * $staycation->house_price;

        // Save booking
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

        // Send confirmation email
        if (Auth::check()) {
            Mail::to(Auth::user()->email)->send(new BookingCreated($booking));
        }

        return redirect()->back()->with('success', 'Booking successfully added! Wait for admin approval.');
    }

    // Preview booking before submission
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

    // Show booking history for logged-in user
    public function bookingHistory()
    {
        $bookings = Booking::where('user_id', Auth::id())->latest()->get();
        return view('home.booking_history', compact('bookings'));
    }

    // Store review for a booking
    public function storeReview(Request $request, $booking_id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        $booking = Booking::findOrFail($booking_id);

        Review::create([
            'user_id' => Auth::id(),
            'booking_id' => $booking->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return back()->with('success', 'Thank you! Your review has been submitted.');
    }

    // Handle contact form submission with optional attachment
    public function sendInquiry(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'message' => 'required|string',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf|max:5120'
        ]);

        $data = [
            'email' => $request->email,
            'message' => $request->message,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $filename);
            $data['attachment'] = $filename;
        }

        DB::table('inquiries')->insert($data);

        return back()->with('success', 'Your message has been sent!');
    }
}
