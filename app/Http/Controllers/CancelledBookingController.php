<?php
// app/Http/Controllers/CancelledBookingController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\BookingHistory;

class CancelledBookingController extends Controller
{
    public function index()
    {
        $cancelledBookings = BookingHistory::with('staycation')
            ->orderByDesc('deleted_at')
            ->get()
            ->map(function ($booking) {
                $booking->formatted_start_date = Carbon::parse($booking->start_date)->format('M d, Y');
                $booking->formatted_end_date   = Carbon::parse($booking->end_date)->format('M d, Y');
                return $booking;
            });

        return view('admin.booking.cancelled', compact('cancelledBookings'));
    }
}