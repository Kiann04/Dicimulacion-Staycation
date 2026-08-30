<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StaycationIndexRequest;
use App\Http\Resources\Api\V1\StaycationResource;
use App\Models\Staycation;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Public, unauthenticated reads of the staycation catalogue.
 *
 * The listing shows only properties that are open for booking, matching what
 * the Blade homepage has always shown; a property an administrator has taken
 * offline is not advertised. The detail endpoint still answers for such a
 * property, with `is_bookable: false`, so a link a guest already holds keeps
 * resolving instead of turning into a 404.
 */
class StaycationController extends Controller
{
    public function index(StaycationIndexRequest $request): AnonymousResourceCollection
    {
        $staycations = Staycation::query()
            ->where('house_availability', 'available')
            ->with('images')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->orderBy('id')
            ->paginate($request->perPage())
            ->withQueryString();

        return StaycationResource::collection($staycations);
    }

    public function show(Staycation $staycation): StaycationResource
    {
        $staycation->loadMissing('images')
            ->loadCount('reviews')
            ->loadAvg('reviews', 'rating');

        return new StaycationResource($staycation);
    }
}
