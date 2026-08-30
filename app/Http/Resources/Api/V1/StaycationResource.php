<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Staycation;
use App\Services\BookingAvailabilityService;
use App\Services\BookingPricingService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The public shape of a staycation, used by both the list and the detail
 * endpoint so a frontend can render either from one type.
 *
 * Only columns a guest may see are exposed. `house_image` and `image_path` hold
 * paths beneath `public/storage`, which are already web-reachable, so they are
 * published as absolute URLs rather than raw paths; nothing from a private disk
 * is ever surfaced here.
 *
 * Money is published twice: `..._formatted` as an exact decimal string for
 * display, and `..._centavos` as an integer for arithmetic. A frontend that
 * multiplies the decimal in a float would reintroduce the drift Money exists to
 * prevent.
 *
 * @mixin Staycation
 */
class StaycationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $pricePerNight = Money::fromDecimal($this->house_price);
        $extraGuestFee = Money::fromDecimal(BookingPricingService::EXTRA_GUEST_FEE);

        return [
            'id' => $this->getKey(),
            'name' => $this->house_name,
            'description' => $this->house_description,
            'location' => $this->house_location,
            'currency' => 'PHP',
            'price_per_night' => $pricePerNight->toDecimalString(),
            'price_per_night_centavos' => $pricePerNight->centavos(),
            'availability_status' => $this->house_availability,
            'is_bookable' => app(BookingAvailabilityService::class)->isOpenForBooking($this->resource),
            'capacity' => [
                'included_guests' => BookingPricingService::INCLUDED_GUESTS,
                'maximum_guests' => BookingPricingService::MAXIMUM_GUESTS,
                'extra_guest_fee' => $extraGuestFee->toDecimalString(),
                'extra_guest_fee_centavos' => $extraGuestFee->centavos(),
            ],
            'rating' => [
                'average' => $this->reviews_avg_rating === null
                    ? null
                    : round((float) $this->reviews_avg_rating, 2),
                'count' => (int) ($this->reviews_count ?? 0),
            ],
            'image_url' => $this->publicImageUrl($this->house_image),
            'gallery' => $this->whenLoaded(
                'images',
                fn () => $this->images
                    ->map(fn ($image): ?string => $this->publicImageUrl($image->image_path))
                    ->filter()
                    ->values()
                    ->all(),
                [],
            ),
        ];
    }

    /**
     * Absolute URL for a path stored beneath `public/storage`.
     */
    private function publicImageUrl(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        return asset('storage/'.ltrim($path, '/'));
    }
}
