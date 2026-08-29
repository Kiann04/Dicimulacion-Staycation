<?php

namespace App\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Staycation
 */
class StaycationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->house_name,
            'description' => $this->house_description,
            'location' => $this->house_location,
            'price_per_night' => (string) $this->house_price,
            'currency' => 'PHP',
            'availability' => $this->house_availability,
            'is_bookable' => $this->isBookable(),
            'image_url' => MediaUrl::public($this->house_image),
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'max_guests' => (int) config('booking.max_guests'),
            'rating' => $this->rating(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Prefers the aggregate columns added by the listing query (withAvg/withCount)
     * and falls back to a loaded reviews relation, so both the index and show
     * endpoints report a rating without either of them triggering N+1 queries.
     *
     * @return array{average: float, count: int}|null
     */
    private function rating(): ?array
    {
        if ($this->reviews_average !== null || $this->reviews_count !== null) {
            return [
                'average' => round((float) $this->reviews_average, 1),
                'count' => (int) $this->reviews_count,
            ];
        }

        if ($this->relationLoaded('reviews')) {
            return [
                'average' => round((float) $this->reviews->avg('rating'), 1),
                'count' => $this->reviews->count(),
            ];
        }

        return null;
    }
}
