<?php
// app/Http/Controllers/CancelledBookingController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CancelledBookingController extends Controller
{
    public function index()
    {
        $cancelledBookings = DB::table('booking_history')
            ->orderBy('deleted_at', 'desc')
            ->get()
            ->map(function ($booking) {
                $booking->formatted_start_date = Carbon::parse($booking->start_date)->format('M d, Y');
                $booking->formatted_end_date   = Carbon::parse($booking->end_date)->format('M d, Y');
                return $booking;
            });

        return view('admin.cancelled', compact('cancelledBookings'));
    }
}
