<?php

namespace App\Http\Requests\Api\Booking;

use App\Http\Requests\Api\ApiFormRequest;

class AvailabilityRequest extends ApiFormRequest
{
    /** Availability is public information; anyone may check a calendar. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after:start_date',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.required' => 'A check-in date is required.',
            'start_date.date_format' => 'The check-in date must be formatted as YYYY-MM-DD.',
            'end_date.required' => 'A check-out date is required.',
            'end_date.date_format' => 'The check-out date must be formatted as YYYY-MM-DD.',
            'end_date.after' => 'The check-out date must be after the check-in date.',
        ];
    }
}
