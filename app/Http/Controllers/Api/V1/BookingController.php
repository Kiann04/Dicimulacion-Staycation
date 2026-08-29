<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Booking\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Staycation;
use App\Services\Booking\BookingService;
use App\Services\Booking\BookingSubmission;
use App\Services\Booking\DateRange;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A customer's own bookings. Every action is scoped to the authenticated user by
 * the query itself and re-checked by BookingPolicy, so an id belonging to someone
 * else is a 404 on listing and a 403 on direct access rather than a data leak.
 */
class BookingController extends Controller
{
    public function __construct(
        private BookingService $bookings,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Booking::class);

        $perPage = min(max($request->integer('per_page', 15), 1), 50);

        $bookings = Booking::query()
            ->where('user_id', $request->user()->getKey())
            ->with(['staycation'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->orderByDesc('start_date')
            ->paginate($perPage)
            ->withQueryString();

        return ApiResponse::paginated(
            BookingResource::collection($bookings->items()),
            $bookings,
        );
    }

    public function show(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('view', $booking);

        $booking->load(['staycation', 'payments']);

        return ApiResponse::success(new BookingResource($booking));
    }

    /**
     * Submits a booking. Availability is re-checked inside a locked transaction
     * and all pricing is recomputed here, so a stale or tampered client payload
     * cannot produce a double booking or an incorrect total.
     */
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $staycation = Staycation::findOrFail($request->integer('staycation_id'));

        $submission = new BookingSubmission(
            range: DateRange::fromInput(
                $request->string('start_date')->toString(),
                $request->string('end_date')->toString(),
            ),
            guestNumber: $request->integer('guest_number'),
            phone: $request->string('phone')->toString(),
            paymentType: $request->string('payment_type')->toString(),
            paymentMethod: $request->string('payment_method')->toString(),
            paymentProof: $request->file('payment_proof'),
            transactionNumber: $request->input('transaction_number'),
            messageToAdmin: $request->input('message_to_admin'),
        );

        $booking = $this->bookings->create($request->user(), $staycation, $submission);

        $booking->load(['staycation', 'payments']);

        return ApiResponse::success(
            new BookingResource($booking),
            201,
            message: 'Your booking has been submitted and is awaiting confirmation.',
        );
    }

    /**
     * Customer cancellation. The booking is kept in the customer's history and
     * moved to the cancelled status, which is what frees the dates.
     */
    public function destroy(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('cancel', $booking);

        $booking = $this->bookings->cancel($booking, $request->user());

        $booking->load(['staycation']);

        return ApiResponse::success(
            new BookingResource($booking),
            message: 'Your booking has been cancelled.',
        );
    }
}
