<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Review;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    // Store review from user
    public function store(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        // Find the booking for the logged-in user
        $booking = Booking::where('id', $request->booking_id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Prevent duplicate reviews
        if ($booking->review) {
            return back()->with('error', 'You already reviewed this booking.');
        }

        // Create review
        Review::create([
            'user_id' => Auth::id(),
            'booking_id' => $booking->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return back()->with('success', 'Thank you! Your review has been submitted.');
    }

    // Admin view for all reviews
    public function adminIndex()
    {
        $reviews = Review::with(['user', 'booking.staycation'])
            ->latest()
            ->paginate(20);

        return view('admin.reviews', compact('reviews'));
    }
}
