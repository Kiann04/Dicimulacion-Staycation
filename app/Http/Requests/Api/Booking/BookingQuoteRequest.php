<?php

namespace App\Http\Requests\Api\Booking;

use App\Http\Requests\Api\ApiFormRequest;

/**
 * A price enquiry. Deliberately accepts no money fields: the server derives
 * every amount and the client may only display what it is told.
 */
class BookingQuoteRequest extends ApiFormRequest
{
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
            'guest_number' => 'required|integer|min:1|max:'.config('booking.max_guests'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'end_date.after' => 'The check-out date must be after the check-in date.',
            'guest_number.max' => 'This staycation accommodates a maximum of :max guests.',
        ];
    }
}
