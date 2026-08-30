<?php

namespace App\Http\Requests;

use App\Services\BookingPricingService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * An administrator editing an existing booking.
 *
 * Holds the same domain invariants as customer booking creation, so a booking
 * cannot be edited into a state a customer could never have created. Past
 * arrival dates are deliberately still allowed here: administrators need to be
 * able to correct historical records.
 */
class UpdateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'staycation_id' => ['required', 'integer', 'exists:staycations,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'guest_number' => ['required', 'integer', 'min:1', 'max:'.BookingPricingService::MAXIMUM_GUESTS],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'staycation_id.exists' => 'The selected staycation no longer exists.',
            'guest_number.max' => 'This staycation accommodates a maximum of :max guests.',
            'guest_number.min' => 'A booking needs at least one guest.',
            'end_date.after' => 'The departure date must be after the arrival date, so the stay is at least one night.',
        ];
    }
}
