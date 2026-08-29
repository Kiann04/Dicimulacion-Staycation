<?php

namespace App\Http\Requests\Api\Admin;

use App\Enums\PaymentStatus;
use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentStatusRequest extends ApiFormRequest
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
            'payment_status' => ['required', 'string', Rule::in(PaymentStatus::adminAssignableValues())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_status.in' => 'The payment status must be one of: '.implode(', ', PaymentStatus::adminAssignableValues()).'.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('payment_status') && is_string($this->input('payment_status'))) {
            $this->merge(['payment_status' => strtolower($this->input('payment_status'))]);
        }
    }
}
