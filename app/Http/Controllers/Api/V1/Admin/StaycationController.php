<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreStaycationRequest;
use App\Http\Requests\Api\Admin\UpdateStaycationRequest;
use App\Http\Resources\StaycationResource;
use App\Models\Staycation;
use App\Services\Audit\AuditLogger;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaycationController extends Controller
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        $staycations = Staycation::query()
            ->withCount(['bookings', 'blockedDates'])
            ->withAvg('reviews as reviews_average', 'rating')
            ->withCount('reviews')
            ->orderBy('house_name')
            ->paginate($perPage)
            ->withQueryString();

        return ApiResponse::paginated(
            StaycationResource::collection($staycations->items()),
            $staycations,
        );
    }

    public function store(StoreStaycationRequest $request): JsonResponse
    {
        $this->authorize('create', Staycation::class);

        $staycation = Staycation::create($request->validated());

        $this->audit->record($request->user(), 'Staycation Created', "Staycation ID: {$staycation->getKey()} created.");

        return ApiResponse::success(new StaycationResource($staycation), 201, message: 'Staycation created.');
    }

    public function show(Staycation $staycation): JsonResponse
    {
        $staycation->load(['images', 'reviews', 'blockedDates']);

        return ApiResponse::success(new StaycationResource($staycation));
    }

    public function update(UpdateStaycationRequest $request, Staycation $staycation): JsonResponse
    {
        $this->authorize('update', $staycation);

        $staycation->update($request->validated());

        $this->audit->record($request->user(), 'Staycation Updated', "Staycation ID: {$staycation->getKey()} updated.");

        return ApiResponse::success(new StaycationResource($staycation->refresh()), message: 'Staycation updated.');
    }

    /**
     * Flips a staycation between available and unavailable. Existing bookings are
     * untouched: taking a listing off sale must not silently void stays that are
     * already confirmed.
     */
    public function toggleAvailability(Request $request, Staycation $staycation): JsonResponse
    {
        $this->authorize('manageAvailability', $staycation);

        $staycation->house_availability = $staycation->isBookable() ? 'unavailable' : 'available';
        $staycation->save();

        $this->audit->record(
            $request->user(),
            'Staycation Availability Changed',
            "Staycation ID: {$staycation->getKey()} set to {$staycation->house_availability}.",
        );

        return ApiResponse::success(
            new StaycationResource($staycation->refresh()),
            message: "Staycation is now {$staycation->house_availability}.",
        );
    }
}
