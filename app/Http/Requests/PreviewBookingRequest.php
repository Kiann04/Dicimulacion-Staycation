<?php

namespace App\Http\Requests;

use App\Services\BookingPricingService;
use Illuminate\Foundation\Http\FormRequest;

class PreviewBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'guest_number' => ['required', 'integer', 'min:1', 'max:'.BookingPricingService::MAXIMUM_GUESTS],
            'startDate' => ['required', 'date', 'after_or_equal:today'],
            'endDate' => ['required', 'date', 'after:startDate'],
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
        ];
    }
}
