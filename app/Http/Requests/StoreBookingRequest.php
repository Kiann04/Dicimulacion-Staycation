<?php

namespace App\Http\Requests;

use App\Services\BookingPricingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'guest_number' => ['required', 'integer', 'min:1', 'max:'.BookingPricingService::MAXIMUM_GUESTS],
            'startDate' => ['required', 'date', 'after_or_equal:today'],
            'endDate' => ['required', 'date', 'after:startDate'],
            'payment_type' => ['required', Rule::in(['half', 'full'])],
            'payment_method' => ['required', Rule::in(['gcash', 'bpi'])],
            'payment_proof' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
            'phone' => ['required', 'string', 'max:20'],
            'transaction_number' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'guest_number.max' => 'This staycation accommodates a maximum of :max guests.',
            'endDate.after' => 'The departure date must be after the arrival date.',
            'startDate.after_or_equal' => 'The arrival date cannot be in the past.',
            'payment_proof.image' => 'The payment proof must be a JPG or PNG image.',
            'payment_proof.max' => 'The payment proof may not be larger than 5 MB.',
        ];
    }
}
