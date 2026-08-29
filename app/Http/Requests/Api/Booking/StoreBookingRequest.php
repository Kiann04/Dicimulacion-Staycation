<?php

namespace App\Http\Requests\Api\Booking;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\Booking;
use Illuminate\Validation\Rule;

/**
 * A booking submission.
 *
 * The client supplies dates, party size, contact details and proof of payment.
 * It supplies no prices: total_price, amount_paid and the deposit split are all
 * computed by BookingPricingService, and any money field sent by a client is
 * ignored outright.
 */
class StoreBookingRequest extends ApiFormRequest
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
            'staycation_id' => 'required|integer|exists:staycations,id',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after:start_date',
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
            'message_to_admin' => 'nullable|string|max:500',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'staycation_id.exists' => 'The selected staycation does not exist.',
            'end_date.after' => 'The check-out date must be after the check-in date.',
            'guest_number.max' => 'This staycation accommodates a maximum of :max guests.',
            'payment_type.in' => 'Choose either a half or a full payment.',
            'payment_method.in' => 'Choose one of the accepted payment methods.',
            'payment_proof.required' => 'A screenshot of your payment is required.',
            'payment_proof.mimetypes' => 'The payment proof must be a JPEG, PNG or WebP image.',
            'payment_proof.max' => 'The payment proof may not be larger than :max kilobytes.',
        ];
    }
}
