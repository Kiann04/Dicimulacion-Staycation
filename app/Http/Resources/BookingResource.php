<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Booking
 */
class BookingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => 'BK-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT),
            'status' => $this->status,
            'blocks_availability' => $this->blocksAvailability(),
            'guest' => [
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'guest_number' => (int) $this->guest_number,
            ],
            'stay' => [
                'start_date' => $this->start_date?->toDateString(),
                'end_date' => $this->end_date?->toDateString(),
                'nights' => $this->start_date && $this->end_date
                    ? (int) $this->start_date->diffInDays($this->end_date)
                    : null,
            ],
            'pricing' => [
                'price_per_night' => (string) $this->price_per_day,
                'total_price' => (string) $this->total_price,
                'amount_paid' => (string) ($this->amount_paid ?? '0.00'),
                'balance_due' => $this->balanceDue(),
                'currency' => 'PHP',
            ],
            'payment' => [
                'status' => $this->payment_status,
                'method' => $this->payment_method,
                'transaction_number' => $this->transaction_number,
                // The stored path is never sent. This URL is an authorized
                // endpoint that streams the file only to the owner or the back
                // office; it is not a public or guessable location.
                'proof_url' => $this->payment_proof
                    ? route('api.v1.bookings.proof', ['booking' => $this->id])
                    : null,
            ],
            'message_to_admin' => $this->message_to_admin,
            'staycation' => new StaycationResource($this->whenLoaded('staycation')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'can' => [
                'cancel' => $request->user()?->can('cancel', $this->resource) ?? false,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
