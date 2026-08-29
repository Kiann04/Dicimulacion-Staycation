<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Services\Audit\AuditLogger;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        $reviews = Review::query()
            ->with(['user', 'staycation'])
            ->when($request->filled('staycation_id'), fn ($query) => $query->where('staycation_id', $request->integer('staycation_id')))
            ->when($request->filled('rating'), fn ($query) => $query->where('rating', $request->integer('rating')))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return ApiResponse::paginated(
            ReviewResource::collection($reviews->items()),
            $reviews,
            [
                'average_rating' => round((float) Review::avg('rating'), 2),
            ],
        );
    }

    public function destroy(Request $request, Review $review): JsonResponse
    {
        $review->delete();

        $this->audit->record($request->user(), 'Review Deleted', "Review ID: {$review->getKey()} removed.");

        return ApiResponse::success(['deleted' => true], message: 'Review removed.');
    }
}
