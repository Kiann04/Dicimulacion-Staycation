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
use App\Models\Staycation;
use App\Services\Booking\BookingAvailabilityService;
use App\Services\Booking\DateRange;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (Blade application)
|--------------------------------------------------------------------------
|
| Route names are load-bearing: the Blade views reference them by name, so the
| names below are preserved exactly even where the URI or middleware changed.
|
| Consolidated in this pass:
|  - /terms was declared twice; the second declaration silently replaced the
|    first, so only the surviving one is kept.
|  - BookingHistory.index and booking.preview were each declared twice.
|  - The admin update_booking routes were unnamed inside a named group, which
|    gave them both the name "admin." and made that name ambiguous.
|  - admin.admin.messages / admin.admin.cancelled / admin.admin.bookings.cancelled
|    were produced by applying an "admin." prefix to names that already carried
|    it. No view referenced them; they have been dropped in favour of the
|    correctly named routes that already existed.
|  - Every admin route now sits inside the admin middleware group. Staff account
|    management, report generation, staycation editing and mark-as-fully-paid
|    were previously reachable by any visitor.
|
*/

// =========================
// Public pages
// =========================
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::view('/terms', 'home.Terms&Condition')->name('terms');
Route::view('/privacy', 'privacy')->name('privacy');

Route::post('/contact/send', [HomeController::class, 'sendInquiry'])->name('contact.send');
Route::post('/save-consent', [ConsentPopupController::class, 'save'])->name('save.consent');

// =========================
// Password reset
// =========================
Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
    ->middleware('guest')
    ->name('password.reset');

Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('password.update');

// =========================
// Auth (customer)
// =========================
Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register'])->name('register.perform');

Route::get('/login', [LoginController::class, 'showUserLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'userLogin'])->name('user.login');

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// =========================
// Auth (admin + staff)
// =========================
Route::get('/admin/login', [LoginController::class, 'showAdminStaffLoginForm'])->name('admin.staff.login');
Route::post('/admin/login', [LoginController::class, 'adminStaffLogin'])->name('admin.staff.login.perform');

// =========================
// Booking
// =========================
// Declared before /booking/{id} so the literal segment is not swallowed by the
// wildcard, which is what previously made /booking/history unreachable.
Route::get('/booking/history', [BookingHistoryController::class, 'index'])
    ->middleware('auth')
    ->name('BookingHistory.index');

Route::get('/booking-history', [BookingHistoryController::class, 'index'])
    ->middleware('auth')
    ->name('BookingHistory.indexAlias');

// The booking form itself stays public so visitors can browse a staycation and
// its calendar; the form's submit control is already gated behind @auth.
Route::get('/booking/{id}', [BookingHistoryController::class, 'bookingForm'])->name('booking.form');
Route::get('/booking/form/{id}', [BookingHistoryController::class, 'bookingForm'])->name('BookingHistory.bookingForm');

// Everything that reads or writes a customer's own booking requires a session.
Route::middleware('auth')->group(function () {
    Route::post('/booking/{id}/preview', [BookingHistoryController::class, 'previewBooking'])->name('booking.preview');
    Route::post('/booking/{id}/submit', [BookingHistoryController::class, 'submitRequest'])->name('booking.submit');
    Route::delete('/booking/{id}/cancel', [BookingHistoryController::class, 'cancel'])->name('BookingHistory.cancel');

    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');
});

// =========================
// Chatbot
// =========================
Route::post('/offline-chat', [OfflineChatBotController::class, 'chat']);
Route::get('/offline-chat', fn () => view('offline-chat'));

Route::view('/chatbot', 'chatbot');
Route::post('/chatbot', [ChatBotController::class, 'ask'])->name('chatbot.ask');
Route::post('/chat', [ChatBotController::class, 'ask']);

// =========================
// Calendar events (public availability feed)
// =========================
/**
 * Only bookings in a blocking status are published to the calendar, so a
 * cancelled or declined booking no longer paints its dates as unavailable.
 */
Route::get('/events/{staycation_id}', function (int $staycation_id, BookingAvailabilityService $availability) {
    $staycation = Staycation::findOrFail($staycation_id);

    $window = DateRange::fromInput(
        now()->subYear()->toDateString(),
        now()->addYears(2)->toDateString(),
    );

    $events = collect($availability->occupiedRanges($staycation, $window))
        ->map(fn (array $range) => [
            'title' => $range['type'] === 'booking' ? 'Booked' : ($range['reason'] ?? 'Blocked'),
            'start' => $range['start_date'],
            // FullCalendar treats "end" as exclusive; the stored end_date is the
            // check-out day, which is already exclusive of the last night.
            'end' => $range['end_date'],
            'display' => 'background',
            'color' => $range['type'] === 'booking' ? '#f87171' : '#6b7280',
            'className' => $range['type'] === 'booking' ? 'booked-date' : 'blocked-date',
            'allDay' => true,
        ])
        ->values();

    return response()->json($events);
})->name('staycation.events');

// =========================
// Dashboard (Jetstream)
// =========================
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');
});

// =========================
// Profile
// =========================
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    Route::get('/test-2fa', fn () => view('test-2fa'))->name('test-2fa');
});

// =========================
// Admin
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
        Route::get('/addproduct', [AdminController::class, 'addProduct'])->name('addproduct');
        Route::get('/audit-logs', [AdminController::class, 'auditLogs'])->name('audit.logs');
        Route::get('/reviews', [ReviewController::class, 'adminIndex'])->name('reviews');

        // Staycations
        Route::post('/staycations/store', [StaycationController::class, 'store'])->name('staycations.store');
        Route::post('/staycations/{id}/toggle-availability', [AdminController::class, 'toggleAvailability'])
            ->name('toggle_availability');
        Route::get('/staycation/{id}', [StaycationController::class, 'showStaycation'])->name('staycation.show');
        Route::get('/staycation/{id}/edit', [StaycationController::class, 'edit'])->name('edit_staycation');
        Route::put('/staycation/{id}', [StaycationController::class, 'update'])->name('update_staycation');

        // Bookings
        Route::get('/bookings', [StaycationController::class, 'index'])->name('bookings');
        Route::get('/view_bookings', [AdminController::class, 'view_bookings'])->name('view_bookings');
        Route::get('/view_bookings/{staycation_id}', [AdminController::class, 'view_staycation_bookings'])
            ->name('view_staycation_bookings');
        Route::get('/update_booking/{id}', [AdminController::class, 'editBooking'])->name('bookings.edit');
        Route::put('/update_booking/{id}', [AdminController::class, 'updateBooking'])->name('bookings.update');
        Route::delete('/bookings/{id}', [AdminController::class, 'deleteBooking'])->name('bookings.delete');

        Route::get('/unpaid-count', [AdminBookingController::class, 'getUnpaidCount'])->name('unpaid.count');

        // Booking filters. Declared before /bookings/{id} style routes so the
        // literal segments are matched first.
        Route::get('/bookings/paid', [BookingHistoryController::class, 'showPaid'])->name('bookings.paid');
        Route::get('/bookings/half-paid', [BookingHistoryController::class, 'showHalfPaid'])->name('bookings.half_paid');
        Route::get('/bookings/cancelled', [CancelledBookingController::class, 'index'])->name('bookings.cancelled');
        Route::get('/cancelled', [CancelledBookingController::class, 'index'])->name('cancelled');

        // Booking actions
        Route::post('/bookings/{id}/approve', [AdminBookingController::class, 'approveBooking'])->name('bookings.approve');
        Route::post('/bookings/{id}/decline', [AdminBookingController::class, 'declineBooking'])->name('bookings.decline');
        Route::post('/bookings/{id}/update-payment', [AdminBookingController::class, 'updatePayment'])->name('bookings.updatePayment');
        Route::post('/bookings/{id}/mark-paid', [BookingHistoryController::class, 'markAsPaid'])->name('bookings.markAsPaid');
        Route::post('/bookings/{id}/mark-fully-paid', [AdminBookingController::class, 'markAsFullyPaid'])
            ->name('bookings.markFullyPaid');

        // Payment proofs. The JSON endpoint returns booking figures plus a link to
        // the streaming route below; neither exposes a public file URL.
        Route::get('/bookings/{id}/proof', [AdminBookingController::class, 'getProof'])->name('bookings.proof');
        Route::get('/bookings/{booking}/proof/file', [AdminBookingController::class, 'showProofFile'])
            ->name('bookings.proof.file');

        // Messages
        Route::get('/view_messages/{id}', [AdminController::class, 'viewMessage'])->name('view_messages');
        Route::get('/messages/delete/{id}', [AdminController::class, 'deleteMessage'])->name('delete_message');
        Route::get('/messages/{id}/reply', [AdminController::class, 'replyMessageForm'])->name('reply_message');
        Route::post('/messages/{id}/reply', [AdminController::class, 'sendReplyMessage'])->name('send_reply');
        Route::get('/messages-payments', [AdminController::class, 'messagesAndPayments'])->name('messages_payments');

        // Reports
        Route::post('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
        Route::get('/reports/download/{type}/{year}/{month?}/{week?}', [ReportController::class, 'download'])
            ->name('reports.download');

        // Blocked dates
        Route::get('/blocked-dates', [BlockedDateController::class, 'index'])->name('blocked_dates.index');
        Route::post('/blocked-dates', [BlockedDateController::class, 'store'])->name('blocked_dates.store');
        Route::get('/events/{staycationId}', [BlockedDateController::class, 'getEvents'])->name('events');

        // Staff management. These were previously reachable without any
        // authentication at all, which allowed anyone to create or delete a staff
        // account.
        Route::get('/add-staff', [AdminController::class, 'addStaff'])->name('addStaff');
        Route::post('/create-staff', [AdminController::class, 'createStaff'])->name('createStaff');
        Route::get('/staff/list', [AdminController::class, 'index'])->name('staffList');
        Route::delete('/staff/delete/{id}', [AdminController::class, 'destroy'])->name('deleteStaff');
    });

// =========================
// Staff
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

        Route::get('/settings', [StaffController::class, 'settings'])->name('settings');
    });
