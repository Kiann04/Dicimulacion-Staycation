<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\DeclineBookingRequest;
use App\Http\Requests\Api\Admin\UpdatePaymentStatusRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\Booking\BookingService;
use App\Services\Payment\PaymentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Back-office booking management.
 *
 * Listing is available to staff and admins; every mutation goes through
 * BookingPolicy::manage, which admits admins only. The state transitions
 * themselves live in BookingService and PaymentService so this controller and
 * the legacy Blade controller cannot drift apart.
 */
class BookingController extends Controller
{
    public function __construct(
        private BookingService $bookings,
        private PaymentService $payments,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        $bookings = Booking::query()
            ->with(['staycation', 'user'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('payment_status'), fn ($query) => $query->where('payment_status', $request->string('payment_status')->toString()))
            ->when($request->filled('staycation_id'), fn ($query) => $query->where('staycation_id', $request->integer('staycation_id')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('start_date', '>=', $request->string('from')->toString()))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('end_date', '<=', $request->string('to')->toString()))
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search')->toString().'%';

                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('transaction_number', 'like', $term);
                });
            })
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return ApiResponse::paginated(
            BookingResource::collection($bookings->items()),
            $bookings,
        );
    }

    public function show(Booking $booking): JsonResponse
    {
        $this->authorize('view', $booking);

        $booking->load(['staycation', 'user', 'payments.verifier']);

        return ApiResponse::success(new BookingResource($booking));
    }

    public function approve(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('manage', $booking);

        $booking = $this->bookings->approve($booking, $request->user());

        return ApiResponse::success(
            new BookingResource($booking->load('staycation')),
            message: 'Booking approved and awaiting payment.',
        );
    }

    public function decline(DeclineBookingRequest $request, Booking $booking): JsonResponse
    {
        $this->authorize('manage', $booking);

        $booking = $this->bookings->decline($booking, $request->user(), $request->input('reason'));

        return ApiResponse::success(
            new BookingResource($booking->load('staycation')),
            message: 'Booking declined. The dates are available again.',
        );
    }

    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('manage', $booking);

        $booking = $this->bookings->cancelAsAdmin($booking, $request->user(), $request->input('reason'));

        return ApiResponse::success(
            new BookingResource($booking->load('staycation')),
            message: 'Booking cancelled. The dates are available again.',
        );
    }

    public function updatePaymentStatus(UpdatePaymentStatusRequest $request, Booking $booking): JsonResponse
    {
        $this->authorize('manage', $booking);

        $booking = $this->payments->applyPaymentStatus(
            $booking,
            $request->string('payment_status')->toString(),
            $request->user(),
        );

        return ApiResponse::success(
            new BookingResource($booking->load(['staycation', 'payments'])),
            message: 'Payment status updated.',
        );
    }

    public function markFullyPaid(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('manage', $booking);

        $booking = $this->payments->markAsFullyPaid($booking, $request->user());

        return ApiResponse::success(
            new BookingResource($booking->load(['staycation', 'payments'])),
            message: 'Booking marked as fully paid.',
        );
    }
}
