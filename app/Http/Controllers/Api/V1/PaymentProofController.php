<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\Payment\PaymentProofStorage;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The only route by which a payment proof leaves the server.
 *
 * Proofs live on a private disk outside the web root under a UUID filename, so
 * there is no URL to guess. Every retrieval passes through BookingPolicy, which
 * admits the booking's owner and the back office and nobody else.
 */
class PaymentProofController extends Controller
{
    public function __construct(
        private PaymentProofStorage $proofs,
    ) {}

    public function show(Request $request, Booking $booking): Response
    {
        $this->authorize('viewPaymentProof', $booking);

        $response = $this->proofs->download(
            $booking->payment_proof,
            'payment-proof-'.$booking->getKey(),
        );

        if ($response === null) {
            return ApiResponse::error('No payment proof is on file for this booking.', 404, 'proof_not_found');
        }

        return $response;
    }
}
