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
    // Store review for a booking
    public function storeReview(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        $booking = Booking::where('id', $request->booking_id)
            ->where('user_id', Auth::id()) // ensures user owns this booking
            ->firstOrFail();

        // ✅ Prevent duplicate reviews
        if ($booking->review) {
            return back()->with('error', 'You have already submitted a review for this booking.');
        }

        // ✅ Create the review
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
    public function privacy()
    {
        return view('privacy');
    }
}
