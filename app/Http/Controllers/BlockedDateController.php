<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingRuleViolation;
use App\Http\Requests\StoreBlockedDateRequest;
use App\Models\BlockedDate;
use App\Models\Staycation;
use App\Services\BookingAvailabilityService;
use App\Services\BookingInventoryService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class BlockedDateController extends Controller
{
    public function __construct(
        private readonly BookingAvailabilityService $availability,
        private readonly BookingInventoryService $inventory,
    ) {}

    public function index(): View
    {
        $blockedDates = BlockedDate::query()
            ->with('staycation')
            ->orderBy('start_date')
            ->get();

        $staycations = Staycation::all();

        return view('admin.block_dates', compact('blockedDates', 'staycations'));
    }

    /**
     * Block a date range.
     *
     * Refused if an active booking already holds a night inside the range: a
     * guest with a confirmed stay must not have the property blocked out from
     * under them. The check runs under the same staycation lock that booking
     * creation takes, so a booking cannot slip in alongside it.
     */
    public function store(StoreBlockedDateRequest $request): RedirectResponse
    {
        try {
            $this->inventory->createBlockedDate(
                (int) $request->validated('staycation_id'),
                CarbonImmutable::parse($request->validated('start_date'))->startOfDay(),
                CarbonImmutable::parse($request->validated('end_date'))->startOfDay(),
                $request->validated('reason'),
            );
        } catch (BookingRuleViolation $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Date blocked successfully.');
    }

    /**
     * Calendar events for booked and blocked nights.
     */
    public function getEvents(int $staycationId): JsonResponse
    {
        return response()->json($this->availability->calendarEvents($staycationId));
    }
}
