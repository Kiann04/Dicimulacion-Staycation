<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Payment
 */
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'amount' => (string) $this->amount,
            'type' => $this->type,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'reference_number' => $this->reference_number,
            // proof_path is deliberately absent; proofs are fetched through the
            // authorized booking proof endpoint.
            'has_proof' => $this->proof_path !== null,
            'verified_at' => $this->verified_at?->toIso8601String(),
            'verified_by' => $this->whenLoaded('verifier', fn () => [
                'id' => $this->verifier?->id,
                'name' => $this->verifier?->name,
            ]),
            'notes' => $this->when($request->user()?->isStaffOrAdmin() ?? false, $this->notes),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
