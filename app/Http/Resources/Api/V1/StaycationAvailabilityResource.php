<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The answer to "can this range be booked right now?".
 *
 * This is a read of the calendar at one instant, not a hold: nothing is
 * reserved by asking, and the range can be taken by another guest a moment
 * later. Booking creation re-checks the same rules under the staycation lock,
 * so an `available: true` here is a hint for the UI, never a promise.
 *
 * @property array{staycation_id: int, start_date: string, end_date: string, nights: int, available: bool, unavailable_reasons: array<int, string>} $resource
 */
class StaycationAvailabilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'staycation_id' => $this->resource['staycation_id'],
            'start_date' => $this->resource['start_date'],
            'end_date' => $this->resource['end_date'],
            'nights' => $this->resource['nights'],
            'available' => $this->resource['available'],
            'unavailable_reasons' => $this->resource['unavailable_reasons'],
            'reserves_inventory' => false,
        ];
    }
}
