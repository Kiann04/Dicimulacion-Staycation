<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Booking\AvailabilityRequest;
use App\Http\Requests\Api\Booking\BookingQuoteRequest;
use App\Http\Resources\StaycationResource;
use App\Models\Staycation;
use App\Services\Booking\BookingAvailabilityService;
use App\Services\Booking\BookingPricingService;
use App\Services\Booking\DateRange;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public catalogue and availability. No authentication required, so the
 * marketing pages can be statically rendered on Vercel.
 */
class StaycationController extends Controller
{
    public function __construct(
        private BookingAvailabilityService $availability,
        private BookingPricingService $pricing,
    ) {}

    /**
     * Paginated catalogue. Supports ?available_only=1, a ?search= term over name
     * and location, and ?per_page= capped at 50 so a client cannot ask for the
     * whole table in one request.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 15), 1), 50);

        $staycations = Staycation::query()
            ->with(['images'])
            ->withAvg('reviews as reviews_average', 'rating')
            ->withCount('reviews')
            ->when($request->boolean('available_only'), fn ($query) => $query->available())
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search')->toString().'%';

                $query->where(function ($inner) use ($term) {
                    $inner->where('house_name', 'like', $term)
                        ->orWhere('house_location', 'like', $term);
                });
            })
            ->orderBy('house_name')
            ->paginate($perPage)
            ->withQueryString();

        return ApiResponse::paginated(
            StaycationResource::collection($staycations->items()),
            $staycations,
        );
    }

    public function show(Staycation $staycation): JsonResponse
    {
        $staycation->load(['images', 'reviews']);

        return ApiResponse::success(new StaycationResource($staycation));
    }

    /**
     * Answers "are these dates free" for one staycation, and returns the exact
     * conflicts when they are not so the client can explain the refusal.
     */
    public function availability(AvailabilityRequest $request, Staycation $staycation): JsonResponse
    {
        $range = DateRange::fromInput(
            $request->string('start_date')->toString(),
            $request->string('end_date')->toString(),
        );

        $conflicts = $this->availability->conflicts($staycation, $range);

        return ApiResponse::success([
            'staycation_id' => $staycation->id,
            'start_date' => $range->startString(),
            'end_date' => $range->endString(),
            'nights' => $range->nights(),
            'is_available' => $conflicts === [] && $staycation->isBookable(),
            'is_bookable' => $staycation->isBookable(),
            'conflicts' => $conflicts,
        ]);
    }

    /**
     * The authoritative price for a stay. The client displays this and never
     * computes its own totals.
     */
    public function quote(BookingQuoteRequest $request, Staycation $staycation): JsonResponse
    {
        $range = DateRange::fromInput(
            $request->string('start_date')->toString(),
            $request->string('end_date')->toString(),
        );

        $quote = $this->pricing->quote($staycation, $range, $request->integer('guest_number'));

        $conflicts = $this->availability->conflicts($staycation, $range);

        return ApiResponse::success([
            'staycation_id' => $staycation->id,
            'is_available' => $conflicts === [] && $staycation->isBookable(),
            'conflicts' => $conflicts,
            'quote' => $quote->toArray(),
        ]);
    }
}
