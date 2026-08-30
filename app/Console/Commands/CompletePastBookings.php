<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Exceptions\InvalidBookingTransition;
use App\Models\Booking;
use App\Services\BookingPaymentService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Mark stays whose checkout day has passed as completed.
 *
 * This used to run as a side effect of rendering the admin dashboard, which
 * meant opening a read-only page rewrote booking rows — and meant the sweep
 * only ran when somebody happened to look. It is now an explicit command,
 * scheduled daily in routes/console.php, and needs nothing beyond cron.
 */
class CompletePastBookings extends Command
{
    protected $signature = 'bookings:complete-past';

    protected $description = 'Mark bookings whose checkout date has passed as completed';

    public function handle(BookingPaymentService $payments): int
    {
        $today = CarbonImmutable::today()->toDateString();

        $bookingIds = Booking::query()
            ->where('end_date', '<', $today)
            ->whereIn('status', [
                BookingStatus::Approved->value,
                BookingStatus::Confirmed->value,
            ])
            ->pluck('id');

        $completed = 0;

        foreach ($bookingIds as $bookingId) {
            try {
                // Each booking is transitioned under its own row lock, so a sweep
                // running alongside an administrator's edit cannot clobber it.
                $payments->complete($bookingId);
                $completed++;
            } catch (InvalidBookingTransition $exception) {
                // The booking moved on between the scan and the lock; leave it be.
                $this->warn("Skipped booking {$bookingId}: {$exception->getMessage()}");
            }
        }

        $this->info("Completed {$completed} booking(s).");

        return self::SUCCESS;
    }
}
