<?php

namespace App\Http\Requests\Api\Admin;

use App\Enums\PaymentType;
use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class RecordPaymentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01|max:9999999.99',
            'type' => ['required', Rule::in(PaymentType::values())],
            'payment_method' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ];
    }
}
