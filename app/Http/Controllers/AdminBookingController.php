<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Http\Requests\Api\Admin\UpdatePaymentStatusRequest;
use App\Mail\BookingApproved;
use App\Mail\BookingDeclined;
use App\Mail\PaymentReceiptMail;
use App\Models\Booking;
use App\Services\Booking\BookingService;
use App\Services\Booking\Exceptions\BookingException;
use App\Services\Payment\PaymentProofStorage;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Admin booking actions for the Blade back office.
 *
 * State transitions, audit logging and money handling are delegated to
 * BookingService and PaymentService; this controller is responsible only for
 * HTTP concerns and notification email.
 */
class AdminBookingController extends Controller
{
    public function __construct(
        private BookingService $bookings,
        private PaymentService $payments,
        private PaymentProofStorage $proofs,
    ) {}

    public function approveBooking(Request $request, int $id): RedirectResponse
    {
        $booking = Booking::with('user', 'staycation')->findOrFail($id);

        $this->authorize('manage', $booking);

        try {
            $booking = $this->bookings->approve($booking, $request->user());
        } catch (BookingException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $this->notify($booking, fn () => new BookingApproved($booking));

        return back()->with('success', 'Booking approved, audit log created, and email sent.');
    }

    public function declineBooking(Request $request, int $id): RedirectResponse
    {
        $booking = Booking::with('user', 'staycation')->findOrFail($id);

        $this->authorize('manage', $booking);

        $booking = $this->bookings->decline($booking, $request->user(), $request->input('reason'));

        $this->notify($booking, fn () => new BookingDeclined($booking));

        return back()->with('success', 'Booking declined and email sent.');
    }

    /**
     * Applies an admin-selected payment status. The status is now validated
     * against the enum rather than trusted from the request, and amount_paid is
     * kept consistent with it by PaymentService.
     */
    public function updatePayment(UpdatePaymentStatusRequest $request, int $id): JsonResponse|RedirectResponse
    {
        $booking = Booking::with('user', 'staycation')->findOrFail($id);

        $this->authorize('manage', $booking);

        $booking = $this->payments->applyPaymentStatus(
            $booking,
            $request->string('payment_status')->toString(),
            $request->user(),
        );

        if (in_array($booking->payment_status, [PaymentStatus::Paid->value, PaymentStatus::HalfPaid->value], true)) {
            $this->notify($booking, fn () => new PaymentReceiptMail($booking));
        }

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Payment status updated successfully!',
                'booking_status' => $booking->status,
                'payment_status' => $booking->payment_status,
                'amount_paid' => (string) $booking->amount_paid,
            ]);
        }

        return redirect()->back()->with('success', 'Payment status updated successfully!');
    }

    public function getUnpaidCount(): JsonResponse
    {
        $count = Booking::whereIn('payment_status', [
            PaymentStatus::Unpaid->value,
            PaymentStatus::Pending->value,
        ])->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Booking summary for the admin proof modal.
     *
     * The proof itself is no longer returned as a public asset URL. The response
     * carries a link to the authorized streaming route instead, so the file
     * cannot be opened by anyone who happens to learn the filename.
     */
    public function getProof(int $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        $this->authorize('viewPaymentProof', $booking);

        return response()->json([
            'id' => $booking->getKey(),
            'start_date' => $booking->formatted_start_date,
            'end_date' => $booking->formatted_end_date,
            'total_price' => (string) $booking->total_price,
            'amount_paid' => (string) ($booking->amount_paid ?? '0.00'),
            'balance_due' => $booking->balanceDue(),
            'proof' => $this->proofs->exists($booking->payment_proof)
                ? route('admin.bookings.proof.file', ['booking' => $booking->getKey()])
                : null,
        ]);
    }

    /** Streams a payment proof to an authorized back-office user. */
    public function showProofFile(int $id): Response
    {
        $booking = Booking::findOrFail($id);

        $this->authorize('viewPaymentProof', $booking);

        $response = $this->proofs->download($booking->payment_proof, 'payment-proof-'.$booking->getKey());

        abort_if($response === null, 404, 'No payment proof is on file for this booking.');

        return $response;
    }

    /**
     * Settles the remaining balance on a half-paid booking.
     *
     * Previously this set payment_status to "paid" without touching amount_paid,
     * so receipts and reports continued to show only the deposit. PaymentService
     * now writes the balance to the ledger and sets amount_paid to the total.
     */
    public function markAsFullyPaid(Request $request, int $id): RedirectResponse
    {
        $booking = Booking::with('user', 'staycation')->findOrFail($id);

        $this->authorize('manage', $booking);

        try {
            $booking = $this->payments->markAsFullyPaid($booking, $request->user());
        } catch (BookingException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        $this->notify($booking, fn () => new PaymentReceiptMail($booking));

        return redirect()->back()->with('success', 'Booking marked as fully paid.');
    }

    /**
     * Sends a customer notification without letting a mail failure roll back or
     * mask the state change that has already been committed.
     */
    private function notify(Booking $booking, callable $mailableFactory): void
    {
        $recipient = $booking->user->email ?? $booking->email;

        if (empty($recipient)) {
            return;
        }

        try {
            Mail::to($recipient)->send($mailableFactory());
        } catch (Throwable $exception) {
            Log::warning('Failed to send booking notification email.', [
                'booking_id' => $booking->getKey(),
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
