<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Exceptions\InvalidBookingTransition;
use App\Http\Requests\UpdateBookingPaymentRequest;
use App\Mail\BookingApproved;
use App\Mail\BookingDeclined;
use App\Mail\PaymentReceiptMail;
use App\Models\Booking;
use App\Models\BookingHistory;
use App\Services\BookingPaymentService;
use App\Services\PaymentProofService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;

class AdminBookingController extends Controller
{
    public function __construct(
        private readonly PaymentProofService $paymentProofs,
        private readonly BookingPaymentService $payments,
    ) {}

    /**
     * Approve a booking so the customer can settle payment.
     */
    public function approveBooking(int $id): RedirectResponse
    {
        $booking = Booking::with(['user', 'staycation'])->findOrFail($id);

        try {
            $this->payments->approve($booking);
        } catch (InvalidBookingTransition $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $this->notify($booking, BookingApproved::class);

        return back()->with('success', 'Booking approved, audit log created, and email sent.');
    }

    /**
     * Decline a booking, releasing its dates back to the calendar.
     */
    public function declineBooking(int $id): RedirectResponse
    {
        $booking = Booking::with(['user', 'staycation'])->findOrFail($id);

        try {
            $this->payments->decline($booking);
        } catch (InvalidBookingTransition $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $this->notify($booking, BookingDeclined::class);

        return back()->with('success', 'Booking declined and email sent.');
    }

    /**
     * Record a payment verification decision.
     */
    public function updatePayment(UpdateBookingPaymentRequest $request, int $id): JsonResponse|RedirectResponse
    {
        $booking = Booking::with(['user', 'staycation'])->findOrFail($id);
        $target = $request->paymentStatus();

        try {
            $this->payments->verifyPayment($booking, $target);
        } catch (InvalidBookingTransition $exception) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return back()->with('error', $exception->getMessage());
        }

        if ($target->isVerified()) {
            $this->notify($booking, PaymentReceiptMail::class);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Payment status updated successfully!',
                'booking_status' => $booking->status,
            ]);
        }

        return back()->with('success', 'Payment status updated successfully!');
    }

    public function getUnpaidCount(): JsonResponse
    {
        return response()->json([
            'count' => Booking::query()
                ->whereIn('payment_status', [PaymentStatus::Unpaid->value, PaymentStatus::Pending->value])
                ->count(),
        ]);
    }

    /**
     * Booking payment metadata for the admin dashboard modal.
     */
    public function getProof(int $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        return response()->json([
            'id' => $booking->getKey(),
            'start_date' => $booking->formatted_start_date,
            'end_date' => $booking->formatted_end_date,
            'total_price' => $booking->total_price,
            'amount_paid' => $booking->amount_paid,
            'declared_amount' => $booking->declared_amount,
            'payment_status' => $booking->payment_status,
            'remaining_balance' => $booking->remainingBalance()->toDecimalString(),
            'proof' => $this->paymentProofs->exists($booking->payment_proof)
                ? route('bookings.payment_proof', $booking)
                : null,
        ]);
    }

    /**
     * Stream a payment proof to an authorized viewer.
     */
    public function showProof(Booking $booking): Response
    {
        $this->authorize('viewPaymentProof', $booking);

        return $this->paymentProofs->response($booking->payment_proof);
    }

    /**
     * Stream an archived booking's payment proof to an authorized viewer.
     *
     * Same rule as a live booking: the administrator, or the customer whose
     * booking it was. Archiving must not change who may read the document.
     */
    public function showArchivedProof(BookingHistory $bookingHistory): Response
    {
        $this->authorize('viewPaymentProof', $bookingHistory);

        return $this->paymentProofs->response($bookingHistory->payment_proof);
    }

    /**
     * Settle the outstanding half of a partially paid booking.
     */
    public function markAsFullyPaid(Request $request, int $id): RedirectResponse
    {
        $booking = Booking::with(['user', 'staycation'])->findOrFail($id);

        try {
            $this->payments->settleRemainingBalance($booking);
        } catch (InvalidBookingTransition $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $this->notify($booking, PaymentReceiptMail::class);

        return back()->with('success', 'Booking marked as fully paid.');
    }

    /**
     * @param  class-string<\Illuminate\Mail\Mailable>  $mailable
     */
    private function notify(Booking $booking, string $mailable): void
    {
        $recipient = $booking->user->email ?? $booking->email;

        if (filled($recipient)) {
            Mail::to($recipient)->send(new $mailable($booking));
        }
    }
}
