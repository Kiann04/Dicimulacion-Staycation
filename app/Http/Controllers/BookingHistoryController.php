<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Http\Requests\PreviewBookingRequest;
use App\Http\Requests\StoreBookingWebRequest;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Review;
use App\Models\Staycation;
use App\Services\Booking\BookingAvailabilityService;
use App\Services\Booking\BookingService;
use App\Services\Booking\BookingSubmission;
use App\Services\Booking\DateRange;
use App\Services\Booking\Exceptions\BookingException;
use App\Services\Payment\PaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The customer-facing Blade booking flow.
 *
 * Availability, pricing and creation are delegated to the booking services, so
 * this controller and the v1 API enforce identical rules. It contains no
 * date-overlap arithmetic and no price arithmetic of its own.
 */
class BookingHistoryController extends Controller
{
    public function __construct(
        private BookingAvailabilityService $availability,
        private BookingService $bookings,
        private PaymentService $payments,
    ) {}

    public function bookingForm(int $id): View
    {
        $staycation = Staycation::findOrFail($id);

        $reviews = Review::where('staycation_id', $id)->get();

        $starCounts = [
            5 => $reviews->where('rating', 5)->count(),
            4 => $reviews->where('rating', 4)->count(),
            3 => $reviews->where('rating', 3)->count(),
            2 => $reviews->where('rating', 2)->count(),
            1 => $reviews->where('rating', 1)->count(),
        ];

        $totalReviews = $reviews->count();
        $averageRating = $totalReviews > 0 ? round($reviews->avg('rating'), 1) : 0;

        $availableStaycations = Staycation::query()
            ->available()
            ->whereKeyNot($staycation->getKey())
            ->get();

        return view('home.Booking', compact(
            'staycation',
            'reviews',
            'starCounts',
            'averageRating',
            'totalReviews',
            'availableStaycations',
        ));
    }

    /**
     * Step 1: confirm the dates are free and show the server-computed price.
     * The figure shown here is recomputed on submission, so a stale preview can
     * never become the price actually charged.
     */
    public function previewBooking(PreviewBookingRequest $request, int $staycation_id): View|RedirectResponse
    {
        $staycation = Staycation::findOrFail($staycation_id);

        $range = DateRange::fromInput($request->string('startDate')->toString(), $request->string('endDate')->toString());

        $conflicts = $this->availability->conflicts($staycation, $range);

        if ($conflicts !== [] || ! $staycation->isBookable()) {
            return back()->with([
                'message' => "⚠️ The selected dates are not available for {$staycation->house_name}.",
                'availableStaycations' => $this->availability->alternatives($range, $staycation->getKey()),
                'startDate' => $request->string('startDate')->toString(),
                'endDate' => $request->string('endDate')->toString(),
                'guest_number' => $request->integer('guest_number'),
                'name' => $request->string('name')->toString(),
                'phone' => $request->string('phone')->toString(),
            ]);
        }

        $quote = $this->bookings->quote($staycation, $range, $request->integer('guest_number'));

        return view('home.preview_booking', [
            'staycation' => $staycation,
            'name' => $request->string('name')->toString(),
            'phone' => $request->string('phone')->toString(),
            'guest_number' => $request->integer('guest_number'),
            'startDate' => $range->startString(),
            'endDate' => $range->endString(),
            'totalPrice' => $quote->totalPrice,
            'quote' => $quote,
        ])->with('success', '✅ Dates are available! Please confirm your booking.');
    }

    /**
     * Step 2: create the booking. Availability is re-checked inside a locked
     * transaction by BookingService, which is what closes the window between
     * preview and submission.
     */
    public function submitRequest(StoreBookingWebRequest $request, int $staycation_id): RedirectResponse
    {
        $staycation = Staycation::findOrFail($staycation_id);

        try {
            $submission = new BookingSubmission(
                range: DateRange::fromInput(
                    $request->string('startDate')->toString(),
                    $request->string('endDate')->toString(),
                ),
                guestNumber: $request->integer('guest_number'),
                phone: $request->string('phone')->toString(),
                paymentType: $request->string('payment_type')->toString(),
                paymentMethod: $request->string('payment_method')->toString(),
                paymentProof: $request->file('payment_proof'),
                transactionNumber: $request->input('transaction_number'),
                messageToAdmin: $request->input('message'),
            );

            $this->bookings->create($request->user(), $staycation, $submission);
        } catch (BookingException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()->route('BookingHistory.index')
            ->with('success', 'Your booking has been submitted! Please wait for admin confirmation.');
    }

    public function index(): View
    {
        $bookings = Booking::query()
            ->where('user_id', Auth::id())
            ->with('staycation')
            ->orderBy('start_date', 'desc')
            ->get();

        return view('home.Booking_History', compact('bookings'));
    }

    /**
     * Customer cancellation.
     *
     * Previously this hard-deleted the row, which erased the customer's history
     * and any record of the payment. It now moves the booking to the cancelled
     * status, which is what releases the dates, and archives it for the admin
     * cancelled-bookings screen.
     */
    public function cancel(Request $request, int $id): RedirectResponse
    {
        $booking = Booking::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $this->authorize('cancel', $booking);

        try {
            $this->bookings->cancel($booking, $request->user());
        } catch (BookingException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('BookingHistory.index')
            ->with('success', 'Booking cancelled successfully.');
    }

    public function showPaid(): View
    {
        $bookings = Booking::where('payment_status', PaymentStatus::Paid->value)
            ->with('staycation')
            ->latest()
            ->get();

        $staycations = Staycation::all();

        return view('admin.paid_bookings', compact('bookings', 'staycations'))->with('filter', 'Paid Bookings');
    }

    public function showHalfPaid(): View
    {
        $bookings = Booking::where('payment_status', PaymentStatus::HalfPaid->value)
            ->with('staycation')
            ->latest()
            ->get();

        $staycations = Staycation::all();

        return view('admin.half_paid_bookings', compact('bookings', 'staycations'))->with('filter', 'Half-Paid Bookings');
    }

    /**
     * Marks a booking settled from the admin listing. Delegates to PaymentService
     * so amount_paid and the ledger are updated too, not just the status flag.
     */
    public function markAsPaid(Request $request, int $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        $this->authorize('manage', $booking);

        $booking = $this->payments->applyPaymentStatus($booking, PaymentStatus::Paid->value, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Booking marked as fully paid!',
            'id' => $booking->getKey(),
            'amount_paid' => (string) $booking->amount_paid,
        ]);
    }

    public function showBookingForm(int $id): View
    {
        $staycation = Staycation::findOrFail($id);
        $allStaycations = Staycation::available()->get();
        $blockedDates = BlockedDate::where('staycation_id', $id)->get();

        return view('booking.form', compact('staycation', 'allStaycations', 'blockedDates'));
    }
}
