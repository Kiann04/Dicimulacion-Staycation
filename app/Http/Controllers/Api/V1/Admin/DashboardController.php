<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Staycation;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Back-office summary figures. Each metric is a single aggregate query rather
 * than a collection walk, so the endpoint stays cheap on shared hosting.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $today = now()->toDateString();

        return ApiResponse::success([
            'bookings' => [
                'total' => Booking::count(),
                'pending' => Booking::where('status', BookingStatus::Pending->value)->count(),
                'approved' => Booking::where('status', BookingStatus::Approved->value)->count(),
                'confirmed' => Booking::where('status', BookingStatus::Confirmed->value)->count(),
                'cancelled' => Booking::where('status', BookingStatus::Cancelled->value)->count(),
                'declined' => Booking::where('status', BookingStatus::Declined->value)->count(),
                'arriving_today' => Booking::whereDate('start_date', $today)->blockingAvailability()->count(),
                'in_house' => Booking::where('start_date', '<=', $today)
                    ->where('end_date', '>', $today)
                    ->blockingAvailability()
                    ->count(),
            ],
            'payments' => [
                'awaiting_verification' => Payment::where('status', PaymentRecordStatus::Pending->value)->count(),
                'unpaid_bookings' => Booking::whereIn('payment_status', [
                    PaymentStatus::Unpaid->value,
                    PaymentStatus::Pending->value,
                ])->count(),
                'half_paid_bookings' => Booking::where('payment_status', PaymentStatus::HalfPaid->value)->count(),
                'collected_total' => (string) Payment::verified()->sum('amount'),
                'expected_total' => (string) Booking::blockingAvailability()->sum('total_price'),
                'outstanding_total' => (string) Booking::blockingAvailability()
                    ->selectRaw('COALESCE(SUM(total_price - COALESCE(amount_paid, 0)), 0) as outstanding')
                    ->value('outstanding'),
            ],
            'catalogue' => [
                'staycations' => Staycation::count(),
                'available' => Staycation::available()->count(),
            ],
            'customers' => [
                'total' => User::where('usertype', 'user')->count(),
                'staff' => User::where('usertype', 'staff')->count(),
            ],
            'generated_at' => now()->toIso8601String(),
        ]);
    }
}
