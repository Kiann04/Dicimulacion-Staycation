<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Staycation;
use App\Models\Review;
use Carbon\Carbon;

class StaycationController extends Controller
{
    // ✅ Show list of staycations for admin
    public function index()
    {
        $staycations = Staycation::all(); 
        return view('admin.bookings', compact('staycations')); 
    }

    // ✅ Add new staycation
    public function store(Request $request)
    {
        $validated = $request->validate([
            'house_name' => 'required|string|max:255',
            'house_description' => 'required|string',
            'house_price' => 'required|numeric|min:0',
            'house_image' => 'required|image|mimes:jpg,png,jpeg|max:5120',
            'house_images.*' => 'image|mimes:jpg,png,jpeg|max:5120', // for gallery
            'house_location' => 'required|string|max:255',
            'house_availability' => 'required|string',
        ]);

        // ✅ Save main image
        $imageName = time() . '.' . $request->house_image->extension();
        $request->house_image->move(public_path('storage/staycations'), $imageName);
        $imagePath = 'staycations/' . $imageName;

        // ✅ Create staycation record
        $staycation = Staycation::create([
            'house_name' => $validated['house_name'],
            'house_description' => $validated['house_description'],
            'house_price' => $validated['house_price'],
            'house_image' => $imagePath,
            'house_location' => $validated['house_location'],
            'house_availability' => $validated['house_availability'],
        ]);

        // ✅ Save multiple gallery images
        if ($request->hasFile('house_images')) {
            foreach ($request->file('house_images') as $image) {
                $galleryName = time() . '-' . uniqid() . '.' . $image->extension();
                $image->move(public_path('storage/staycations/gallery'), $galleryName);

                $staycation->images()->create([
                    'image_path' => 'staycations/gallery/' . $galleryName,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Staycation house added successfully!');
    }


    // ✅ Show single staycation details + all reviews for that staycation
    public function showStaycation($id)
    {   
        $staycation = Staycation::findOrFail($id);

        // Fetch reviews linked through bookings
        $allReviews = Review::with(['user', 'booking.staycation'])
            ->whereHas('booking', function ($query) use ($id) {
                $query->where('staycation_id', $id);
            })
            ->latest()
            ->get();

        return view('home.booking', compact('staycation', 'allReviews'));
    }

    // ✅ Show all reviews (for separate reviews page)
    public function allReviews()
    {
        $allReviews = Review::with(['user', 'booking.staycation'])
                            ->latest()
                            ->paginate(12);

        return view('home.AllReviews', compact('allReviews'));
    }
    public function edit($id)
    {
        $staycation = Staycation::findOrFail($id);
        return view('admin.staycations.edit', compact('staycation'));
    }

}
