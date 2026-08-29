<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\PaymentRecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\RecordPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The payment ledger. Verifying or rejecting a row is what moves money on a
 * booking; bookings.amount_paid and payment_status are recalculated from the
 * ledger by PaymentService on every change.
 */
class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $payments,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        $payments = Payment::query()
            ->with(['booking.staycation', 'verifier'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->when($request->filled('booking_id'), fn ($query) => $query->where('booking_id', $request->integer('booking_id')))
            ->when($request->boolean('awaiting_verification'), fn ($query) => $query->pending())
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return ApiResponse::paginated(
            PaymentResource::collection($payments->items()),
            $payments,
            [
                'pending_count' => Payment::where('status', PaymentRecordStatus::Pending->value)->count(),
                'verified_total' => (string) Payment::verified()->sum('amount'),
            ],
        );
    }

    public function show(Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment);

        $payment->load(['booking.staycation', 'verifier']);

        return ApiResponse::success(new PaymentResource($payment));
    }

    /** Records money an admin has confirmed arriving outside the normal flow. */
    public function store(RecordPaymentRequest $request, Booking $booking): JsonResponse
    {
        $this->authorize('manage', $booking);

        $payment = $this->payments->recordVerifiedPayment(
            booking: $booking,
            amount: (string) $request->input('amount'),
            type: $request->string('type')->toString(),
            verifier: $request->user(),
            method: $request->input('payment_method'),
            reference: $request->input('reference_number'),
            notes: $request->input('notes'),
        );

        return ApiResponse::success(
            new PaymentResource($payment->load('verifier')),
            201,
            message: 'Payment recorded.',
        );
    }

    public function verify(Request $request, Payment $payment): JsonResponse
    {
        $this->authorize('verify', $payment);

        $payment = $this->payments->verify($payment, $request->user());

        return ApiResponse::success(
            new PaymentResource($payment->load(['booking', 'verifier'])),
            message: 'Payment verified.',
        );
    }

    public function reject(Request $request, Payment $payment): JsonResponse
    {
        $this->authorize('reject', $payment);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $payment = $this->payments->reject($payment, $request->user(), $validated['notes'] ?? null);

        return ApiResponse::success(
            new PaymentResource($payment->load(['booking', 'verifier'])),
            message: 'Payment rejected.',
        );
    }
}
