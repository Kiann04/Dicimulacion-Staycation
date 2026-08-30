<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Exceptions\BookingRuleViolation;
use App\Exceptions\PaymentProofStorageFailure;
use App\Http\Requests\PreviewBookingRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Review;
use App\Models\Staycation;
use App\Services\BookingAvailabilityService;
use App\Services\BookingInventoryService;
use App\Services\BookingPaymentService;
use App\Services\BookingPricingService;
use App\Services\PaymentProofService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class BookingHistoryController extends Controller
{
    public function __construct(
        private readonly BookingAvailabilityService $availability,
        private readonly BookingInventoryService $inventory,
        private readonly BookingPricingService $pricing,
        private readonly BookingPaymentService $payments,
        private readonly PaymentProofService $paymentProofs,
    ) {}

    /**
     * Show the booking form for a staycation.
     */
    public function bookingForm(int $id): View
    {
        $staycation = Staycation::findOrFail($id);

        $reviews = Review::query()->where('staycation_id', $id)->get();

        $starCounts = collect(range(1, 5))
            ->mapWithKeys(fn (int $rating): array => [
                $rating => $reviews->where('rating', $rating)->count(),
            ])
            ->all();

        $totalReviews = $reviews->count();
        $averageRating = $totalReviews > 0 ? round($reviews->avg('rating'), 1) : 0;

        $availableStaycations = Staycation::query()
            ->whereKeyNot($staycation->getKey())
            ->where('house_availability', 'available')
            ->get();

        return view('home.Booking', compact(
            'staycation',
            'reviews',
            'starCounts',
            'averageRating',
            'totalReviews',
            'availableStaycations'
        ));
    }

    /**
     * Step 1: price and availability preview before the customer commits.
     *
     * Advisory only. Nothing here is trusted by the submission, which repeats
     * every check under the staycation lock.
     */
    public function previewBooking(PreviewBookingRequest $request, int $staycation_id): View|RedirectResponse
    {
        $staycation = Staycation::findOrFail($staycation_id);

        $startDate = CarbonImmutable::parse($request->validated('startDate'))->startOfDay();
        $endDate = CarbonImmutable::parse($request->validated('endDate'))->startOfDay();
        $guestNumber = (int) $request->validated('guest_number');

        $isBookable = $this->availability->isOpenForBooking($staycation)
            && $this->availability->isAvailable($staycation->getKey(), $startDate, $endDate);

        if (! $isBookable) {
            $message = $this->availability->isOpenForBooking($staycation)
                ? "⚠️ The selected dates are not available for {$staycation->house_name}."
                : "⚠️ {$staycation->house_name} is not currently open for booking.";

            return back()->with([
                'message' => $message,
                'availableStaycations' => $this->availability->alternativeStaycations(
                    $staycation->getKey(),
                    $startDate,
                    $endDate
                ),
                'startDate' => $request->validated('startDate'),
                'endDate' => $request->validated('endDate'),
                'guest_number' => $guestNumber,
                'name' => $request->validated('name'),
                'phone' => $request->validated('phone'),
            ]);
        }

        $quote = $this->pricing->quote($staycation, $guestNumber, $startDate, $endDate);

        return view('home.preview_booking', [
            'staycation' => $staycation,
            'name' => $request->validated('name'),
            'phone' => $request->validated('phone'),
            'guest_number' => $guestNumber,
            'startDate' => $request->validated('startDate'),
            'endDate' => $request->validated('endDate'),
            'totalPrice' => $quote['total_price']->toFloat(),
            'quote' => $quote,
        ])->with('success', '✅ Dates are available! Please confirm your booking.');
    }

    /**
     * Step 2: persist the booking.
     *
     * The proof is written first because it cannot be part of the database
     * transaction; if anything after that fails, the orphaned file is removed so
     * storage never accumulates proofs no booking refers to.
     */
    public function submitRequest(StoreBookingRequest $request, int $staycation_id): RedirectResponse
    {
        $staycation = Staycation::findOrFail($staycation_id);

        $startDate = CarbonImmutable::parse($request->validated('startDate'))->startOfDay();
        $endDate = CarbonImmutable::parse($request->validated('endDate'))->startOfDay();

        try {
            $proofPath = $this->paymentProofs->store($request->file('payment_proof'));
        } catch (PaymentProofStorageFailure $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        $persisted = false;

        try {
            $this->inventory->createBooking(
                $staycation->getKey(),
                $request->user(),
                (int) $request->validated('guest_number'),
                $startDate,
                $endDate,
                [
                    'phone' => $request->validated('phone'),
                    'payment_type' => $request->validated('payment_type'),
                    'payment_method' => $request->validated('payment_method'),
                    'payment_proof' => $proofPath,
                    'transaction_number' => $request->validated('transaction_number'),
                    'message' => $request->validated('message'),
                ],
            );

            $persisted = true;
        } catch (BookingRuleViolation $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        } finally {
            if (! $persisted) {
                $this->paymentProofs->delete($proofPath);
            }
        }

        return redirect()->route('BookingHistory.index')
            ->with('success', 'Your booking has been submitted! Please wait for admin confirmation.');
    }

    /**
     * The signed-in customer's own bookings.
     */
    public function index(): View
    {
        $bookings = Booking::query()
            ->with(['staycation', 'review'])
            ->where('user_id', Auth::id())
            ->orderByDesc('start_date')
            ->get();

        return view('home.Booking_History', compact('bookings'));
    }

    /**
     * Cancel a pending booking the customer owns.
     *
     * Any payment proof already on file is kept: it is the evidence for whatever
     * refund conversation follows, and destroying it here would erase history.
     */
    public function cancel(Request $request, int $id): RedirectResponse
    {
        $booking = Booking::findOrFail($id);

        // A cheap early rejection so an obviously unrelated request never reaches
        // the service. It is deliberately NOT the authoritative check: the
        // booking's status can change between here and the lock, so the real
        // decision is made inside the transaction against the locked row.
        $this->authorize('cancel', $booking);

        try {
            $this->payments->cancel($booking, $request->user());
        } catch (AuthorizationException $exception) {
            throw new AccessDeniedHttpException($exception->getMessage(), $exception);
        } catch (BookingRuleViolation $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $booking->delete();

        return redirect()->route('BookingHistory.index')
            ->with('success', 'Booking cancelled successfully.');
    }

    public function showPaid(): View
    {
        $bookings = Booking::query()
            ->with(['user', 'staycation'])
            ->where('payment_status', PaymentStatus::Paid->value)
            ->latest()
            ->get();

        $staycations = Staycation::all();

        return view('admin.paid_bookings', compact('bookings', 'staycations'))
            ->with('filter', 'Paid Bookings');
    }

    public function showHalfPaid(): View
    {
        $bookings = Booking::query()
            ->with(['user', 'staycation'])
            ->where('payment_status', PaymentStatus::HalfPaid->value)
            ->latest()
            ->get();

        $staycations = Staycation::all();

        return view('admin.half_paid_bookings', compact('bookings', 'staycations'))
            ->with('filter', 'Half-Paid Bookings');
    }

    /**
     * Settle a booking in full from the half-paid filter screen.
     */
    public function markAsPaid(int $id): JsonResponse
    {
        $booking = Booking::with('staycation')->findOrFail($id);

        try {
            $this->payments->verifyPayment($booking, PaymentStatus::Paid);
        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking marked as fully paid!',
            'id' => $booking->getKey(),
        ]);
    }

    public function showBookingForm(int $id): View
    {
        $staycation = Staycation::findOrFail($id);
        $allStaycations = Staycation::query()->where('house_availability', 'available')->get();
        $blockedDates = BlockedDate::query()->where('staycation_id', $id)->get();

        return view('booking.form', compact('staycation', 'allStaycations', 'blockedDates'));
    }
}
