<?php

namespace App\Services;

use App\Exceptions\BookingNotArchivable;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Moving a booking out of the live table and into the archive.
 *
 * The archive row and the deletion are one transaction. Written as two separate
 * statements they could half-happen: an archive row with the booking still live
 * (a duplicate waiting to be created again on retry), or a deleted booking with
 * no record of it ever having existed.
 *
 * Locking convention: booking row only. A hard delete releases inventory rather
 * than taking it, so it cannot cause a double booking; what it does need is to
 * serialize against other state changes to the same booking, and to make a
 * repeated or concurrent request a no-op rather than a second archive row.
 */
class BookingArchiveService
{
    private const DEADLOCK_ATTEMPTS = 3;

    /**
     * Archive a booking and remove it from the live table, atomically.
     *
     * A second concurrent call blocks on the row lock and then finds the booking
     * gone, so it cannot produce a duplicate archive entry.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException when the
     *                                                              booking has already been archived
     */
    public function archiveAndDelete(Booking|int $booking): BookingHistory
    {
        $bookingId = $booking instanceof Booking ? $booking->getKey() : $booking;

        return DB::transaction(function () use ($bookingId): BookingHistory {
            $locked = Booking::query()->lockForUpdate()->findOrFail($bookingId);

            // Read the eligibility rule from the locked row, never from whatever
            // the controller loaded earlier: between that read and this lock an
            // administrator may have verified the payment, and hard-deleting a
            // booking whose money has been received destroys the only record of it.
            $this->assertArchivable($locked);

            $history = BookingHistory::create([
                'booking_id' => $locked->getKey(),
                'user_id' => $locked->user_id,
                'name' => $locked->name,
                'staycation_id' => $locked->staycation_id,
                'start_date' => $locked->start_date?->toDateString(),
                'end_date' => $locked->end_date?->toDateString(),
                'total_price' => $locked->total_price,
                'payment_status' => $locked->payment_status,

                // The proof travels with the archive: it is the evidence for any
                // refund conversation, so it is never destroyed here.
                'payment_proof' => $locked->payment_proof,

                'action_by' => Auth::user()?->name ?? 'System',
                'action_at' => now(),
                'deleted_at' => now(),
            ]);

            $locked->forceDelete();

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'Booking Deleted',
                'description' => "Booking ID: {$bookingId} permanently deleted and copied to history.",
                'ip_address' => request()->ip(),
            ]);

            return $history;
        }, self::DEADLOCK_ATTEMPTS);
    }

    /**
     * A booking whose money has been verified is never hard-deleted.
     *
     * There is no refund or reversal workflow yet, so destroying the record
     * would leave received money with nothing to account for it. One that is
     * merely awaiting verification carries no such record and may go.
     *
     * @throws BookingNotArchivable
     */
    private function assertArchivable(Booking $booking): void
    {
        $paymentStatus = $booking->paymentStatus();

        if ($paymentStatus?->isVerified()) {
            throw BookingNotArchivable::hasVerifiedPayment($paymentStatus);
        }
    }
}
