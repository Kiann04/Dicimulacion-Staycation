<?php

namespace App\Http\Requests;

use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Payment statuses arrive from legacy forms in mixed casing.
     */
    protected function prepareForValidation(): void
    {
        $status = PaymentStatus::fromLoose($this->input('payment_status'));

        if ($status !== null) {
            $this->merge(['payment_status' => $status->value]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'payment_status' => ['required', Rule::in(PaymentStatus::adminAssignableValues())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_status.in' => 'That is not a payment status an administrator can set.',
        ];
    }

    public function paymentStatus(): PaymentStatus
    {
        return PaymentStatus::from($this->validated('payment_status'));
    }
}
