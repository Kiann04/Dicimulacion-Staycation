<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BlockedDate;
use App\Models\Staycation; // add this at the top
use Carbon\Carbon;
use App\Models\Booking;

class BlockedDateController extends Controller
{
    public function index()
    {
        $blockedDates = BlockedDate::with('staycation') // ✅ eager load
                                    ->orderBy('start_date', 'asc')
                                    ->get();
        $staycations = Staycation::all();

        return view('admin.block_dates', compact('blockedDates', 'staycations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'staycation_id' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:255',
        ]);

        BlockedDate::create([
            'staycation_id' => $request->staycation_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
        ]);

        return redirect()->back()->with('success', 'Date blocked successfully.');
    }
    public function getEvents($staycationId)
    {
        // Bookings
        $bookings = Booking::where('staycation_id', $staycationId)
            ->whereNull('deleted_at')
            ->get();

        $bookingEvents = $bookings->map(function ($booking) {
            return [
                'title' => 'Booked',
                'start' => $booking->start_date,
                'end' => Carbon::parse($booking->end_date)->addDay()->toDateString(),
                'color' => '#f56565', // red
                'display' => 'background', // full background
                'allDay' => true,          // important for all-day
            ];
        });

        // Blocked Dates
        $blockedDates = BlockedDate::where('staycation_id', $staycationId)->get();

        $blockedEvents = $blockedDates->map(function ($blocked) {
            return [
                'title' => $blocked->reason ?? 'Blocked',
                'start' => $blocked->start_date,
                'end' => Carbon::parse($blocked->end_date)->addDay()->toDateString(),
                'color' => '#fcd34d', // yellow
                'display' => 'background',
                'allDay' => true,     // important for all-day
            ];
        });

        $events = $bookingEvents->merge($blockedEvents)->values();

        return response()->json($events);
    }
}
