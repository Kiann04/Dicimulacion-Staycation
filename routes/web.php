<?php

use App\Http\Controllers\AdminBookingController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\BlockedDateController;
use App\Http\Controllers\BookingHistoryController;
use App\Http\Controllers\CancelledBookingController;
use App\Http\Controllers\ChatBotController;
use App\Http\Controllers\ConsentPopupController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\OfflineChatBotController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StaycationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Every administrator and staff action lives inside the protected groups at
| the bottom of this file. Nothing that reads or mutates booking, payment,
| customer or staff data belongs outside them.
|
*/

// =========================
// Public Pages
// =========================
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::view('/terms', 'home.Terms&Condition')->name('terms');
Route::get('/privacy', [HomeController::class, 'privacy'])->name('privacy');

// =========================
// Auth (User)
// =========================
Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register'])
    ->middleware('throttle:6,1')
    ->name('register.perform');

Route::get('/login', [LoginController::class, 'showUserLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'userLogin'])
    ->middleware('throttle:6,1')
    ->name('user.login');

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// =========================
// Auth (Admin + Staff)
// =========================
Route::get('/admin/login', [LoginController::class, 'showAdminStaffLoginForm'])->name('admin.staff.login');
Route::post('/admin/login', [LoginController::class, 'adminStaffLogin'])
    ->middleware('throttle:6,1')
    ->name('admin.staff.login.perform');

// =========================
// Password Reset
// =========================
Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
    ->middleware('guest')
    ->name('password.reset');

Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('password.update');

// =========================
// Public Booking Routes
// =========================
Route::get('/booking/{id}', [BookingHistoryController::class, 'bookingForm'])
    ->whereNumber('id')
    ->name('booking.form');

Route::get('/booking/form/{id}', [BookingHistoryController::class, 'bookingForm'])
    ->whereNumber('id')
    ->name('BookingHistory.bookingForm');

Route::post('/booking/preview/{staycation_id}', [BookingHistoryController::class, 'previewBooking'])
    ->whereNumber('staycation_id')
    ->name('booking.preview');

/** Retained so older links to the previous preview URL keep working. */
Route::post('/booking/{staycation_id}/preview', [BookingHistoryController::class, 'previewBooking'])
    ->whereNumber('staycation_id')
    ->name('booking.preview.legacy');

Route::post('/contact/send', [HomeController::class, 'sendInquiry'])->name('contact.send');

// Calendar availability for the public booking form.
Route::get('/events/{staycation_id}', [BlockedDateController::class, 'getEvents'])
    ->whereNumber('staycation_id')
    ->name('staycation.events');

// =========================
// Authenticated Customer Routes
// =========================
Route::middleware('auth')->group(function () {
    Route::post('/booking/{staycation_id}/submit', [BookingHistoryController::class, 'submitRequest'])
        ->whereNumber('staycation_id')
        ->name('booking.submit');

    Route::get('/booking-history', [BookingHistoryController::class, 'index'])->name('BookingHistory.index');

    Route::delete('/booking/{id}/cancel', [BookingHistoryController::class, 'cancel'])
        ->whereNumber('id')
        ->name('BookingHistory.cancel');

    Route::get('/bookings/{booking}/payment-proof', [AdminBookingController::class, 'showProof'])
        ->name('bookings.payment_proof');

    /*
     * Archived bookings keep the same proof and the same access rule, so this
     * sits beside the live route rather than inside the admin group: the
     * customer whose booking it was must still be able to read their own
     * document. BookingHistoryPolicy decides who may.
     */
    Route::get('/booking-history/{bookingHistory}/payment-proof', [AdminBookingController::class, 'showArchivedProof'])
        ->name('booking_history.payment_proof');

    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');

    // Named profile.edit, not profile.show: Jetstream already owns profile.show
    // for /user/profile, and two routes sharing a name makes route() depend on
    // registration order.
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    Route::get('/test-2fa', fn () => view('test-2fa'))->name('test-2fa');
});

// =========================
// Chatbot
// =========================
Route::post('/offline-chat', [OfflineChatBotController::class, 'chat']);
Route::get('/offline-chat', fn () => view('offline-chat'));

Route::view('/chatbot', 'chatbot');
Route::post('/chatbot', [ChatBotController::class, 'ask'])->name('chatbot.ask');
Route::post('/chat', [ChatBotController::class, 'ask']);

Route::post('/chat-gemini', function (Request $request) {
    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
    ])->post(
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key='
            .config('services.gemini.key'),
        [
            'contents' => [
                ['parts' => [['text' => $request->input('message')]]],
            ],
        ]
    );

    $reply = $response->json('candidates.0.content.parts.0.text')
        ?? '🤖 Sorry, I couldn’t understand that.';

    return response()->json(['reply' => $reply]);
})->middleware('throttle:30,1');

Route::post('/save-consent', [ConsentPopupController::class, 'save'])->name('save.consent');

// =========================
// Dashboard (Sanctum/Jetstream)
// =========================
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');
});

// =========================
// Admin Routes
// =========================
Route::prefix('admin')
    ->name('admin.')
    ->middleware([
        'auth:sanctum',
        config('jetstream.auth_session'),
        'verified',
        'admin',
    ])
    ->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/customers', [AdminController::class, 'customers'])->name('customers');
        Route::get('/customers/{id}/bookings', [AdminController::class, 'viewBookings'])->name('customers.bookings');
        Route::get('/analytics', [AdminController::class, 'analytics'])->name('analytics');
        Route::get('/messages', [AdminController::class, 'messages'])->name('messages');
        Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
        Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
        Route::get('/audit-logs', [AdminController::class, 'auditLogs'])->name('audit.logs');
        Route::get('/addproduct', [AdminController::class, 'addProduct'])->name('addproduct');
        Route::get('/reviews', [ReviewController::class, 'adminIndex'])->name('reviews');

        // Staycations
        Route::post('/staycations/store', [StaycationController::class, 'store'])->name('staycations.store');
        Route::post('/staycations/{id}/toggle-availability', [AdminController::class, 'toggleAvailability'])
            ->name('toggle_availability');
        Route::get('/staycation/{id}/edit', [StaycationController::class, 'edit'])->name('edit_staycation');
        Route::put('/staycation/{id}', [StaycationController::class, 'update'])->name('update_staycation');
        Route::get('/staycation/{id}', [StaycationController::class, 'showStaycation'])->name('staycation.show');

        // Bookings
        Route::get('/bookings', [StaycationController::class, 'index'])->name('bookings');
        Route::get('/view_bookings', [AdminController::class, 'view_bookings'])->name('view_bookings');
        Route::get('/view_bookings/{staycation_id}', [AdminController::class, 'view_staycation_bookings'])
            ->name('view_staycation_bookings');
        Route::get('/update_booking/{id}', [AdminController::class, 'editBooking'])->name('bookings.edit');
        Route::put('/update_booking/{id}', [AdminController::class, 'updateBooking'])->name('bookings.update');
        Route::delete('/bookings/{id}', [AdminController::class, 'deleteBooking'])->name('bookings.delete');

        // Booking filters
        Route::get('/bookings/paid', [BookingHistoryController::class, 'showPaid'])->name('bookings.paid');
        Route::get('/bookings/half-paid', [BookingHistoryController::class, 'showHalfPaid'])->name('bookings.half_paid');
        Route::get('/bookings/cancelled', [CancelledBookingController::class, 'index'])->name('bookings.cancelled');
        Route::get('/cancelled', [CancelledBookingController::class, 'index'])->name('cancelled');

        // Booking actions
        Route::get('/unpaid-count', [AdminBookingController::class, 'getUnpaidCount'])->name('unpaid.count');
        Route::post('/bookings/{id}/approve', [AdminBookingController::class, 'approveBooking'])->name('bookings.approve');
        Route::post('/bookings/{id}/decline', [AdminBookingController::class, 'declineBooking'])->name('bookings.decline');
        Route::post('/bookings/{id}/update-payment', [AdminBookingController::class, 'updatePayment'])
            ->name('bookings.updatePayment');
        Route::post('/bookings/{id}/mark-paid', [BookingHistoryController::class, 'markAsPaid'])->name('bookings.markAsPaid');
        Route::post('/bookings/{id}/mark-fully-paid', [AdminBookingController::class, 'markAsFullyPaid'])
            ->name('bookings.markFullyPaid');
        Route::get('/bookings/{id}/proof', [AdminBookingController::class, 'getProof'])->name('bookings.proof');

        // Messages
        Route::get('/messages-payments', [AdminController::class, 'messagesAndPayments'])->name('messages_payments');
        Route::get('/view_messages/{id}', [AdminController::class, 'viewMessage'])->name('view_messages');
        Route::delete('/messages/{id}', [AdminController::class, 'deleteMessage'])->name('delete_message');
        Route::get('/messages/{id}/reply', [AdminController::class, 'replyMessageForm'])->name('reply_message');
        Route::post('/messages/{id}/reply', [AdminController::class, 'sendReplyMessage'])->name('send_reply');

        // Blocked dates
        Route::get('/blocked-dates', [BlockedDateController::class, 'index'])->name('blocked_dates.index');
        Route::post('/blocked-dates', [BlockedDateController::class, 'store'])->name('blocked_dates.store');
        Route::get('/events/{staycationId}', [BlockedDateController::class, 'getEvents'])
            ->whereNumber('staycationId')
            ->name('events');

        // Reports
        Route::post('/reports/generate', [AdminController::class, 'generateReport'])->name('reports.generate');
        Route::get('/reports/download/{id}', [AdminController::class, 'downloadReport'])
            ->whereNumber('id')
            ->name('reports.download');

        // Staff management
        Route::get('/add-staff', [AdminController::class, 'addStaff'])->name('addStaff');
        Route::post('/create-staff', [AdminController::class, 'createStaff'])->name('createStaff');
        Route::get('/staff/list', [AdminController::class, 'index'])->name('staffList');
        Route::delete('/staff/delete/{id}', [AdminController::class, 'destroy'])->name('deleteStaff');
    });

/*
 * Period reports live outside the admin name group because the admin group
 * already owns the `admin.reports.download` name for saved report files.
 */
Route::prefix('admin')
    ->middleware([
        'auth:sanctum',
        config('jetstream.auth_session'),
        'verified',
        'admin',
    ])
    ->group(function () {
        Route::get('/reports/download/{type}/{year}/{month?}/{week?}', [ReportController::class, 'download'])
            ->name('reports.download');
    });

// =========================
// Staff Routes
// =========================
Route::prefix('staff')
    ->name('staff.')
    ->middleware([
        'auth:sanctum',
        config('jetstream.auth_session'),
        'verified',
        'staff',
    ])
    ->group(function () {
        Route::get('/dashboard', [StaffController::class, 'dashboard'])->name('dashboard');

        Route::get('/customers', [StaffController::class, 'customers'])->name('customers');
        Route::get('/customers/{id}/bookings', [StaffController::class, 'viewCustomerBookings'])->name('customers.bookings');

        Route::get('/bookings', [StaffController::class, 'bookings'])->name('bookings');

        Route::get('/messages', [StaffController::class, 'messages'])->name('messages');
        Route::get('/messages/{id}', [StaffController::class, 'viewMessage'])->name('view_message');
        Route::get('/messages/{id}/reply', [StaffController::class, 'replyMessageForm'])->name('reply_message');
        Route::post('/messages/{id}/reply', [StaffController::class, 'sendReplyMessage'])->name('send_reply');
        Route::delete('/messages/{id}', [StaffController::class, 'deleteMessage'])->name('delete_message');
    });
