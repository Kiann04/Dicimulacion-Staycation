<?php

use App\Http\Controllers\Api\V1\StaycationAvailabilityController;
use App\Http\Controllers\Api\V1\StaycationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| The versioned prefix is part of the published contract: /api/v1 may only
| change in backward-compatible ways, and a breaking change opens /api/v2
| beside it rather than editing these routes.
|
| Staycations are addressed by their numeric primary key, matching every
| existing web route (/booking/{id}). The table has no slug column, and adding
| one now would mean a migration plus a backfill for a benefit no consumer has
| asked for. A slug can be introduced later as an *additional* lookup key
| without breaking any client that already stores ids.
|
| The routes below are public and unauthenticated. Authenticated booking and
| administration endpoints are Phase 2B; see docs/api-contract.md.
|
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('/staycations', [StaycationController::class, 'index'])
        ->name('staycations.index');

    Route::get('/staycations/{staycation}', [StaycationController::class, 'show'])
        ->whereNumber('staycation')
        ->name('staycations.show');

    Route::get('/staycations/{staycation}/availability', StaycationAvailabilityController::class)
        ->whereNumber('staycation')
        ->name('staycations.availability');
});
