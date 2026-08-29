<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        $customers = User::query()
            ->where('usertype', 'user')
            ->withCount('bookings')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search')->toString().'%';

                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)->orWhere('email', 'like', $term);
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return ApiResponse::paginated(
            UserResource::collection($customers->items()),
            $customers,
        );
    }

    /** A customer with their booking history, for the back-office profile screen. */
    public function show(User $customer): JsonResponse
    {
        $bookings = $customer->bookings()
            ->with('staycation')
            ->orderByDesc('start_date')
            ->get();

        return ApiResponse::success([
            'customer' => new UserResource($customer),
            'bookings' => BookingResource::collection($bookings),
            'stats' => [
                'total_bookings' => $bookings->count(),
                'lifetime_value' => (string) $bookings->sum(fn ($booking) => (float) ($booking->amount_paid ?? 0)),
            ],
        ]);
    }
}
