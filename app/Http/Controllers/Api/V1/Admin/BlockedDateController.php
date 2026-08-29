<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreBlockedDateRequest;
use App\Http\Resources\BlockedDateResource;
use App\Models\BlockedDate;
use App\Models\Staycation;
use App\Services\Audit\AuditLogger;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlockedDateController extends Controller
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        $blockedDates = BlockedDate::query()
            ->with('staycation')
            ->when($request->filled('staycation_id'), fn ($query) => $query->where('staycation_id', $request->integer('staycation_id')))
            ->orderBy('start_date')
            ->paginate($perPage)
            ->withQueryString();

        return ApiResponse::paginated(
            BlockedDateResource::collection($blockedDates->items()),
            $blockedDates,
        );
    }

    public function store(StoreBlockedDateRequest $request): JsonResponse
    {
        $staycation = Staycation::findOrFail($request->integer('staycation_id'));

        $this->authorize('manageAvailability', $staycation);

        $blockedDate = BlockedDate::create($request->validated());

        $this->audit->record(
            $request->user(),
            'Dates Blocked',
            "Staycation ID: {$staycation->getKey()} blocked from {$blockedDate->start_date->toDateString()} to {$blockedDate->end_date->toDateString()}.",
        );

        return ApiResponse::success(
            new BlockedDateResource($blockedDate->load('staycation')),
            201,
            message: 'Dates blocked.',
        );
    }

    public function destroy(Request $request, BlockedDate $blockedDate): JsonResponse
    {
        $this->authorize('manageAvailability', $blockedDate->staycation);

        $blockedDate->delete();

        $this->audit->record($request->user(), 'Dates Unblocked', "Blocked date ID: {$blockedDate->getKey()} removed.");

        return ApiResponse::success(['deleted' => true], message: 'Blocked dates removed.');
    }
}
