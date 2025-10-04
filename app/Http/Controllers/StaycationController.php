<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Staycation;
use App\Models\Review;
use Carbon\Carbon;

class StaycationController extends Controller
{
    // Display all staycations in the admin bookings page
    public function index()
    {
        $staycations = Staycation::all(); 
        return view('admin.bookings', compact('staycations')); 
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'house_name' => 'required|string|max:255',
            'house_description' => 'required|string',
            'house_price' => 'required|numeric|min:0',
            'house_image' => 'required|image|mimes:jpg,png,jpeg|max:2048',
            'house_location' => 'required|string|max:255',
            'house_availability' => 'required|string',
        ]);

        $imagePath = $request->file('house_image')->store('staycations', 'public');

        Staycation::create([
            'house_name' => $validated['house_name'],
            'house_description' => $validated['house_description'],
            'house_price' => $validated['house_price'],
            'house_image' => $imagePath,
            'house_location' => $validated['house_location'],
            'house_availability' => $validated['house_availability'],
        ]);

        return redirect()->back()->with('success', 'Staycation house added successfully!');
    }

    // ✅ Show booking page with ALL reviews (not per staycation)
    public function bookingPage($id)
    {
        // load staycation
        $staycation = Staycation::findOrFail($id);

        // fetch all reviews with user + booking->staycation
        $allReviews = Review::with(['user', 'booking.staycation'])
                            ->latest()
                            ->get();

        // pass both variables to the view
        return view('home.Booking', compact('staycation', 'allReviews'));
    }
    public function showStaycation($id)
    {
        $staycation = Staycation::findOrFail($id);

        // ✅ Only reviews for this staycation (through bookings)
        $allReviews = Review::with(['user', 'booking.staycation'])
            ->whereHas('booking', function ($query) use ($id) {
                $query->where('staycation_id', $id);
            })
            ->latest()
            ->get();

        return view('home.booking', compact('staycation', 'allReviews'));
    }
    


}
