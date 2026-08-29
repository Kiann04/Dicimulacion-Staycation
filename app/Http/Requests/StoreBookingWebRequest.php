<?php

namespace App\Http\Requests;

use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for the Blade booking form.
 *
 * Mirrors App\Http\Requests\Api\Booking\StoreBookingRequest but keeps the
 * existing form field names (startDate / endDate / message) so the Blade views
 * do not have to change. Like the API request, it accepts no price fields.
 */
class StoreBookingWebRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('create', Booking::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'startDate' => 'required|date_format:Y-m-d',
            'endDate' => 'required|date_format:Y-m-d|after:startDate',
            'guest_number' => 'required|integer|min:1|max:'.config('booking.max_guests'),
            'phone' => 'required|string|max:20',
            'payment_type' => ['required', Rule::in(['half', 'full'])],
            'payment_method' => ['required', Rule::in(config('booking.payment_methods'))],
            'payment_proof' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:'.config('booking.proof_max_kilobytes'),
            ],
            'transaction_number' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:500',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'endDate.after' => 'The check-out date must be after the check-in date.',
            'guest_number.max' => 'This staycation accommodates a maximum of :max guests.',
            'payment_proof.required' => 'A screenshot of your payment is required.',
            'payment_proof.mimetypes' => 'The payment proof must be a JPEG, PNG or WebP image.',
        ];
    }
}
