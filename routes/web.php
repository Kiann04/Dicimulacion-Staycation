<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Auth\NewPasswordController;
Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
    ->middleware('guest')
    ->name('password.reset');

Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('password.update');

Route::get('/terms', function () {
return view('Terms&Condition'); // or 'terms' if your file is named terms.blade.php
})->name('terms');

Route::get('/privacy', function () {
    return view('privacy');
})->name('privacy');
use App\Http\Controllers\{
    LoginController,
    AdminController,
    StaycationController,
    HomeController,
    RegisterController,
    StaffController,
    ReviewController,
    AdminBookingController,
    OfflineChatBotController,
    ChatBotController,
    BookingHistoryController,
    ProfileController
};

Route::middleware(['auth'])->group(function () {
    // Other profile routes...

    // Profile password update
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])
        ->name('profile.password.update');
});
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// =========================
// Public Pages
// =========================
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::view('/terms', 'home.Terms&Condition')->name('terms');


// =========================
// Auth (User)
// =========================
Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register'])->name('register.perform');

Route::get('/login', [LoginController::class, 'showUserLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'userLogin'])->name('user.login');

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('home');
})->name('logout');

// =========================
// Auth (Admin + Staff)
// =========================
Route::get('/admin/login', [LoginController::class, 'showAdminStaffLoginForm'])->name('admin.staff.login');
Route::post('/admin/login', [LoginController::class, 'adminStaffLogin'])->name('admin.staff.login.perform');
// =========================
// PUBLIC BOOKING ROUTES
// =========================

// Booking form
Route::get('/booking/{id}', [BookingHistoryController::class, 'bookingForm'])->name('booking.form');

// Preview booking
Route::post('/booking/{id}/preview', [BookingHistoryController::class, 'previewBooking'])->name('booking.preview');;

// Submit booking
Route::post('/booking/{id}/submit', [BookingHistoryController::class, 'submitRequest'])->name('booking.submit');

// Booking history
Route::get('/booking/history', [BookingHistoryController::class, 'index'])->name('BookingHistory.index');


// Cancel a booking

Route::delete('/booking/{id}/cancel', [BookingHistoryController::class, 'cancel'])->name('BookingHistory.cancel');
Route::get('/booking-history', [BookingHistoryController::class, 'index'])->name('BookingHistory.index')->middleware('auth');

// =========================
// routes/web.php
Route::post('/reviews', [ReviewController::class, 'store'])
    ->middleware('auth')
    ->name('reviews.store');
// =========================
// Contact / Messages
// =========================
Route::post('/contact/send', [HomeController::class, 'sendInquiry'])->name('contact.send');


// =========================
// Chatbot
// =========================
Route::post('/offline-chat', [OfflineChatBotController::class, 'chat']);
Route::get('/offline-chat', fn() => view('offline-chat'));

Route::view('/chatbot', 'chatbot');
Route::post('/chatbot', [ChatBotController::class, 'ask'])->name('chatbot.ask');
Route::post('/chat', [ChatBotController::class, 'ask']); // API endpoint

Route::get('/test-gemini', function () {
    $apiKey = env('GEMINI_API_KEY');
    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
    ])->post("https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
        'contents' => [['parts' => [['text' => 'Hello Gemini']]]],
    ]);
    return $response->json();
});

// =========================
// Calendar Events
// =========================
Route::get('/events/{staycation_id}', function ($staycation_id) {
    return DB::table('bookings')
        ->where('staycation_id', $staycation_id)
        ->select(
            DB::raw("'Booked' as title"),
            DB::raw("start_date as start"),
            // Add 1 day to end_date
            DB::raw("DATE_ADD(end_date, INTERVAL 1 DAY) as end"),
            DB::raw("'background' as display"),
            DB::raw("'booked-date' as className")
        )
        ->get();
});

// =========================
// Dashboard (Sanctum/Jetstream)
// =========================
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', fn() => view('dashboard'))->name('dashboard');
});

// =========================
// Profile
// =========================
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
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
        'admin'
    ])
    ->group(function () {

    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/customers', [AdminController::class, 'customers'])->name('customers');
    Route::get('/analytics', [AdminController::class, 'analytics'])->name('analytics');
    Route::get('/messages', [AdminController::class, 'messages'])->name('messages');
    Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    Route::get('/addproduct', [AdminController::class, 'addProduct'])->name('addproduct');
    Route::post('/staycations/store', [StaycationController::class, 'store'])->name('staycations.store');
    Route::post('/staycations/{id}/toggle-availability', [AdminController::class, 'toggleAvailability'])
         ->name('toggle_availability');
     // ✅ Real-time unpaid booking count route
    Route::get('/unpaid-count', [AdminBookingController::class, 'getUnpaidCount'])
        ->name('unpaid.count');

    // Bookings
    Route::get('/bookings', [StaycationController::class, 'index'])->name('bookings');
    Route::get('/staycation/{id}', [StaycationController::class, 'showStaycation'])->name('staycation.show');
    Route::get('/view_bookings', [AdminController::class, 'view_bookings'])->name('view_bookings');
    Route::get('/view_bookings/{staycation_id}', [AdminController::class, 'view_staycation_bookings'])->name('view_staycation_bookings');
    Route::get('/update_booking/{id}', [AdminController::class, 'editBooking']);
    Route::put('/update_booking/{id}', [AdminController::class, 'updateBooking']);

    // ✅ Add this route to delete unpaid bookings
    Route::delete('/bookings/{id}', [AdminController::class, 'deleteBooking'])->name('bookings.delete');

    // Messages
    Route::get('/view_messages/{id}', [AdminController::class, 'viewMessage'])->name('view_messages');
    Route::get('/messages/delete/{id}', [AdminController::class, 'deleteMessage'])->name('delete_message');
    Route::get('/messages/{id}/reply', [AdminController::class, 'replyMessageForm'])->name('reply_message');
    Route::post('/messages/{id}/reply', [AdminController::class, 'sendReplyMessage'])->name('send_reply');

    // Reports
    Route::post('/reports/generate', [AdminController::class, 'generateReport'])->name('reports.generate');
    Route::get('/reports/download/{id}', [AdminController::class, 'downloadReport'])->name('reports.download');

    // Bookings actions
    Route::post('/bookings/{id}/approve', [AdminBookingController::class, 'approveBooking'])->name('bookings.approve');
    Route::post('/bookings/{id}/decline', [AdminBookingController::class, 'declineBooking'])->name('bookings.decline');
    Route::post('/bookings/{id}/update-payment', [AdminBookingController::class, 'updatePayment'])->name('bookings.updatePayment');

    // Extra
    Route::get('/customers/{id}/bookings', [AdminController::class, 'viewBookings'])->name('customers.bookings');
    Route::get('/audit-logs', [AdminController::class, 'auditLogs'])->name('audit.logs');
    Route::get('/reviews', [ReviewController::class, 'adminIndex'])->name('reviews');

    // Booking Filter Routes
    Route::get('/bookings/paid', [BookingHistoryController::class, 'showPaid'])->name('bookings.paid');
    Route::get('/bookings/half-paid', [BookingHistoryController::class, 'showHalfPaid'])->name('bookings.half_paid');
    Route::post('/bookings/{id}/mark-paid', [BookingHistoryController::class, 'markAsPaid'])->name('bookings.markAsPaid');

    Route::get('/bookings/cancelled', [AdminController::class, 'cancelled'])->name('bookings.cancelled');

    // Admin Messages & Payment Proofs
    Route::get('/messages-payments', [AdminController::class, 'messagesAndPayments'])
     ->name('admin.messages');


});

// =========================
// Staff Routes
Route::prefix('staff')->name('staff.')->middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'staff'
])->group(function () {
    Route::get('/dashboard', [StaffController::class, 'dashboard'])->name('dashboard');

    // Customers
    Route::get('/customers', [StaffController::class, 'customers'])->name('customers');
    Route::get('/customers/{id}/bookings', [StaffController::class, 'viewCustomerBookings'])->name('customers.bookings');

    // Bookings
    Route::get('/bookings', [StaffController::class, 'bookings'])->name('bookings');

    // Messages
    Route::get('/messages', [StaffController::class, 'messages'])->name('messages');
    Route::get('/messages/{id}', [StaffController::class, 'viewMessage'])->name('view_message');
    Route::get('/messages/{id}/reply', [StaffController::class, 'replyMessageForm'])->name('reply_message');
    Route::post('/messages/{id}/reply', [StaffController::class, 'sendReplyMessage'])->name('send_reply');
    Route::delete('/messages/{id}', [StaffController::class, 'deleteMessage'])->name('delete_message');

    // Settings
    Route::get('/settings', [StaffController::class, 'settings'])->name('settings');
});
use App\Http\Controllers\ConsentPopupController;

Route::post('/save-consent', [ConsentPopupController::class, 'save'])->name('save.consent');

Route::middleware(['auth'])->group(function () {
    Route::get('/test-2fa', function () {
        return view('test-2fa');
    })->name('test-2fa');
});
// routes/web.php
use App\Http\Controllers\CancelledBookingController;

Route::prefix('admin')->group(function () {
    Route::get('/cancelled', [CancelledBookingController::class, 'index'])
        ->name('admin.cancelled');
});
