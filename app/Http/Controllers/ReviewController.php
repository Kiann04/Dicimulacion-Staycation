<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Review;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function store(Request $request, $bookingId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        $booking = Booking::findOrFail($bookingId);

        // Prevent duplicate reviews
        if ($booking->review) {
            return back()->with('error', 'You already reviewed this booking.');
        }

        Review::create([
            'user_id' => Auth::id(),
            'booking_id' => $bookingId,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return back()->with('success', 'Review submitted successfully!');
    }

    public function adminIndex()
    {
    // Get all reviews with user info, latest first
    $reviews = Review::with('user')->latest()->paginate(20);

    return view('admin.reviews', compact('reviews'));
    }
}
