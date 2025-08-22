<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Staycation;
use App\Models\Booking;

class HomeController extends Controller
{
    public function index()
    {
        $staycations = Staycation::where('house_availability', 'available')->get();
        return view('home.Homepage', compact('staycations'));
    }

    // Show the booking form
    public function Booking($id)
    {
        $staycation = Staycation::findOrFail($id);
        return view('home.Booking', compact('staycation'));
    }

    // Handle the booking submission
    public function add_booking(Request $request, $id)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'phone' => 'required|string|max:20',
        'guest_number' => 'required|integer|min:1',
        'startDate' => 'required|date',
        'endDate' => 'required|date|after_or_equal:startDate'
    ]);

    $booking = new Booking;
    $booking->staycation_id = $id;
    $booking->name = $request->name;
    $booking->phone = $request->phone;
    $booking->guest_number = $request->guest_number;

    $startDate = $request->startDate;
    $endDate = $request->endDate;
    $isBooked = Booking::where('staycation_id',$id)
    ->where('start_date','<=',$endDate)
    ->where('end_date','>=',$startDate)->exists();
    if($isBooked)
    {
        return redirect()->back()->with('message', "Staycation house is already booked.\nPlease try different dates.");
    }
    else
    {
        $booking->start_date = $request->startDate;
        $booking->end_date = $request->endDate;
        $booking->save();

    return redirect()->back()->with('success', 'Booking successfully added!');
    }
}
public function createBooking($id)
{
    $staycation = Staycation::findOrFail($id);
    return view('booking_form', compact('staycation'));
}
public function send(Request $request)
{
    // Validate form
    $request->validate([
        'email' => 'required|email',
        'message' => 'required|string'
    ]);

    // Store to database
    \DB::table('inquiries')->insert([
        'email' => $request->email,
        'message' => $request->message,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    return back()->with('success', 'Your message has been sent!');
}
}