<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Staycation;
use App\Models\Booking;
use Carbon\Carbon;
use App\Mail\BookingCreated;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

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

    $startDate = $request->startDate;
    $endDate = $request->endDate;

    // Check if already booked
    $isBooked = Booking::where('staycation_id', $id)
        ->where('start_date', '<=', $endDate)
        ->where('end_date', '>=', $startDate)
        ->exists();

    if ($isBooked) {
        return redirect()->back()->with('message', "Staycation house is already booked.\nPlease try different dates.");
    }

    // Save booking
    $booking = new Booking;
    $booking->staycation_id = $id;
    $booking->user_id = auth()->id(); // store user who booked
    $booking->name = $request->name;
    $booking->phone = $request->phone;
    $booking->guest_number = $request->guest_number;
    $booking->start_date = $startDate;
    $booking->end_date = $endDate;
    $booking->status = 'pending';
    $booking->save();

    // Send confirmation email
    if (auth()->check()) {
        Mail::to(auth()->user()->email)->send(new BookingCreated($booking));
    }

    return redirect()->back()->with(
        'success',
        'Booking successfully added! Wait for the admin approval. A confirmation email has been sent.'
    );
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
    public function previewBooking(Request $request, $staycation_id)
{
    $staycation = Staycation::findOrFail($staycation_id);

    // Calculate total days (including last day)
    $days = Carbon::parse($request->startDate)->diffInDays(Carbon::parse($request->endDate)) + 1;

    // Multiply by house price
    $totalPrice = $days * $staycation->price_per_day;

    return view('booking_preview', [
        'staycation'    => $staycation,
        'name'          => $request->name,
        'phone'         => $request->phone,
        'guest_number'  => $request->guest_number,
        'startDate'     => $request->startDate,
        'endDate'       => $request->endDate,
        'totalPrice'    => $totalPrice
    ]);
}
public function bookingHistory()
    {
        // If you have bookings from database, you can pass them here
        // $bookings = auth()->user()->bookings; // Example
        return view('home.booking_history'); // Match your blade file
    }

}
