<?php

use App\Http\Controllers\Api\V1\Admin\BlockedDateController as AdminBlockedDateController;
use App\Http\Controllers\Api\V1\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Api\V1\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Api\V1\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Api\V1\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Api\V1\Admin\StaycationController as AdminStaycationController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\PaymentProofController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\StaycationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
|
| Consumed by the Next.js frontend. Authentication is Sanctum personal access
| tokens sent as "Authorization: Bearer <token>" - the frontend and the API sit
| on different registrable domains, so Sanctum's cookie/SPA mode is not usable.
|
| The Blade application continues to serve routes/web.php unchanged; nothing in
| this file replaces it.
|
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/me', [AuthController::class, 'me'])->name('api.root.auth.me');
});

Route::prefix('v1')->name('api.v1.')->group(function () {

    /*
    | Public
    */

    // Credential endpoints are throttled harder than the rest of the API.
    // AuthController additionally rate limits per email+IP to stop an attacker
    // spreading guesses for one account across many source addresses.
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('auth/register', [AuthController::class, 'register'])->name('auth.register');
        Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');
    });

    Route::get('staycations', [StaycationController::class, 'index'])->name('staycations.index');
    Route::get('staycations/{staycation}', [StaycationController::class, 'show'])->name('staycations.show');
    Route::get('staycations/{staycation}/availability', [StaycationController::class, 'availability'])
        ->name('staycations.availability');
    Route::post('staycations/{staycation}/quote', [StaycationController::class, 'quote'])
        ->name('staycations.quote');

    /*
    | Authenticated
    */

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('auth/logout-all', [AuthController::class, 'logoutAll'])->name('auth.logout-all');

        Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

        Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::post('bookings', [BookingController::class, 'store'])->name('bookings.store');
        Route::get('bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
        Route::delete('bookings/{booking}', [BookingController::class, 'destroy'])->name('bookings.destroy');

        // Payment proofs are private files served only to the booking's owner or
        // the back office. There is no public URL for them anywhere.
        Route::get('bookings/{booking}/proof', [PaymentProofController::class, 'show'])->name('bookings.proof');

        /*
        | Admin operations
        |
        | Restricted exclusively to authenticated users with the "admin" role.
        */

        Route::prefix('admin')->name('admin.')->middleware('back-office')->group(function () {

            Route::get('dashboard', AdminDashboardController::class)->name('dashboard');

            Route::get('bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
            Route::get('bookings/{booking}', [AdminBookingController::class, 'show'])->name('bookings.show');

            Route::get('staycations', [AdminStaycationController::class, 'index'])->name('staycations.index');
            Route::get('staycations/{staycation}', [AdminStaycationController::class, 'show'])->name('staycations.show');

            Route::get('customers', [AdminCustomerController::class, 'index'])->name('customers.index');
            Route::get('customers/{customer}', [AdminCustomerController::class, 'show'])->name('customers.show');

            Route::get('payments', [AdminPaymentController::class, 'index'])->name('payments.index');
            Route::get('payments/{payment}', [AdminPaymentController::class, 'show'])->name('payments.show');

            Route::get('blocked-dates', [AdminBlockedDateController::class, 'index'])->name('blocked-dates.index');

            Route::get('reviews', [AdminReviewController::class, 'index'])->name('reviews.index');

            Route::middleware('admin')->group(function () {

                Route::post('bookings/{booking}/approve', [AdminBookingController::class, 'approve'])->name('bookings.approve');
                Route::post('bookings/{booking}/decline', [AdminBookingController::class, 'decline'])->name('bookings.decline');
                Route::post('bookings/{booking}/cancel', [AdminBookingController::class, 'cancel'])->name('bookings.cancel');
                Route::put('bookings/{booking}/payment-status', [AdminBookingController::class, 'updatePaymentStatus'])
                    ->name('bookings.payment-status');
                Route::post('bookings/{booking}/mark-fully-paid', [AdminBookingController::class, 'markFullyPaid'])
                    ->name('bookings.mark-fully-paid');
                Route::post('bookings/{booking}/payments', [AdminPaymentController::class, 'store'])->name('bookings.payments.store');

                Route::post('staycations', [AdminStaycationController::class, 'store'])->name('staycations.store');
                Route::put('staycations/{staycation}', [AdminStaycationController::class, 'update'])->name('staycations.update');
                Route::post('staycations/{staycation}/toggle-availability', [AdminStaycationController::class, 'toggleAvailability'])
                    ->name('staycations.toggle-availability');

                Route::post('payments/{payment}/verify', [AdminPaymentController::class, 'verify'])->name('payments.verify');
                Route::post('payments/{payment}/reject', [AdminPaymentController::class, 'reject'])->name('payments.reject');

                Route::post('blocked-dates', [AdminBlockedDateController::class, 'store'])->name('blocked-dates.store');
                Route::delete('blocked-dates/{blockedDate}', [AdminBlockedDateController::class, 'destroy'])->name('blocked-dates.destroy');

                Route::delete('reviews/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');
            });
        });
    });
});
