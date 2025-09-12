<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\StaycationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\ReviewController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Here is where you can register web routes for your application.
|
*/

// Homepage
Route::get('/', [HomeController::class, 'index'])->name('home');
// Show booking form (GET)
// Show booking form
Route::get('Booking/{id}', [HomeController::class, 'Booking'])->name('booking.form');

// Handle booking form submission
Route::post('add_booking/{id}', [HomeController::class, 'add_booking'])->name('booking.add');


// Add booking (POST)
Route::post('add_booking/{id}', [HomeController::class, 'add_booking'])->name('booking.add');
// Register routes
Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register'])->name('register.perform');
// USER LOGIN
Route::get('/login', [LoginController::class, 'showUserLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'userLogin'])->name('user.login');
// ADMIN + STAFF LOGIN
Route::get('/admin/login', [LoginController::class, 'showAdminStaffLoginForm'])->name('admin.staff.login');
Route::post('/admin/login', [LoginController::class, 'adminStaffLogin'])->name('admin.staff.login.perform');

// Logout (for both admin and users)
Route::post('/logout', function () {
    Auth::logout();                  
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('home'); // 👈 Redirect to homepage after logout
})->name('logout');

// Dashboard (for authenticated users via Jetstream/Sanctum)
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

// Admin Routes
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/customers', [AdminController::class, 'customers'])->name('customers');
    Route::get('/analytics', [AdminController::class, 'analytics'])->name('analytics');
    Route::get('/messages', [AdminController::class, 'messages'])->name('messages');
    Route::get('/bookings', [StaycationController::class, 'index'])->name('bookings');
    Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    Route::get('/addproduct', [AdminController::class, 'addProduct'])->name('addproduct');
    // Store new staycation
    Route::post('/staycations/store', [StaycationController::class, 'store'])->name('staycations.store');
    
});
Route::prefix('staff')->name('staff.')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [StaffController::class, 'dashboard'])->name('dashboard');
    Route::get('/customers', [StaffController::class, 'customers'])->name('customers');
    Route::get('/analytics', [StaffController::class, 'analytics'])->name('analytics');
    Route::get('/messages', [StaffController::class, 'messages'])->name('messages');
    Route::get('/bookings', [StaycationController::class, 'index'])->name('bookings');
    Route::get('/reports', [StaffController::class, 'reports'])->name('reports');
    Route::get('/settings', [StaffController::class, 'settings'])->name('settings');
    Route::get('/addproduct', [StaffController::class, 'addProduct'])->name('addproduct');
    Route::post('/staycations/store', [StaycationController::class, 'store'])->name('staycations.store');
});


// all bookings
Route::get('/admin/view_bookings', [AdminController::class, 'view_bookings'])
     ->name('admin.view_bookings');

// bookings by staycation
Route::get('/admin/view_bookings/{staycation_id}', [AdminController::class, 'view_staycation_bookings'])
     ->name('admin.view_staycation_bookings');

// Show update form
Route::get('admin/update_booking/{id}', [AdminController::class, 'editBooking']);

// Handle update form submission
Route::put('admin/update_booking/{id}', [AdminController::class, 'updateBooking']);
//route contact
Route::post('/contact/send', [HomeController::class, 'send'])->name('contact.send');
//message
Route::get('/admin/messages', [AdminController::class, 'messages'])->name('admin.messages');
Route::get('/admin/view_messages/{id}', [AdminController::class, 'viewMessage'])->name('admin.view_messages');
Route::get('/admin/messages/delete/{id}', [AdminController::class, 'deleteMessage'])->name('admin.delete_message');
//reports
Route::get('/admin/reports', [AdminController::class, 'reports'])->name('admin.reports');
Route::post('/admin/reports/generate', [AdminController::class, 'generateReport'])->name('admin.reports.generate');
Route::get('/admin/reports/download/{id}', [AdminController::class, 'downloadReport'])->name('admin.reports.download');

// Show reply form
Route::get('/admin/messages/{id}/reply', [AdminController::class, 'replyMessageForm'])->name('admin.reply_message');

// Send reply email
Route::post('/admin/messages/{id}/reply', [AdminController::class, 'sendReplyMessage'])->name('admin.send_reply');
//Calendar
Route::get('/events/{staycation_id}', function ($staycation_id) {
    return DB::table('bookings')
        ->where('staycation_id', $staycation_id) // filter by staycation
        ->select(
            DB::raw("'Booked' as title"),
            DB::raw("start_date as start"),
            DB::raw("end_date as end"),
            DB::raw("'background' as display"),
            DB::raw("'booked-date' as className")
        )
        ->get();
});

//chatbot
use App\Http\Controllers\OfflineChatBotController;

Route::post('/offline-chat', [OfflineChatBotController::class, 'chat']);

Route::get('/offline-chat', function() {
    return view('offline-chat');
});

use App\Http\Controllers\ChatBotController;

Route::post('/chatbot', [ChatBotController::class, 'ask'])->name('chatbot.ask');


Route::view('/chatbot', 'chatbot');

// API endpoint for Gemini
Route::post('/chat', [\App\Http\Controllers\ChatBotController::class, 'ask']);

use Illuminate\Support\Facades\Http;

Route::get('/test-gemini', function () {
    $apiKey = env('GEMINI_API_KEY');

    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
    ])->post("https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
        'contents' => [
            ['parts' => [['text' => 'Hello Gemini']]]
        ]
    ]);

    return $response->json();
});
Route::post('/preview_booking/{staycation_id}', [HomeController::class, 'previewBooking'])
    ->middleware('auth');

Route::post('/admin/bookings/{id}/approve', [AdminController::class, 'approveBooking'])->name('admin.bookings.approve');
Route::post('/admin/bookings/{id}/decline', [AdminController::class, 'declineBooking'])->name('admin.bookings.decline');

use App\Http\Controllers\AdminBookingController;

Route::post('/admin/bookings/{id}/approve', [AdminBookingController::class, 'approve'])->name('admin.bookings.approve');

Route::post('/admin/bookings/{id}/approve', [AdminController::class, 'approveBooking'])->name('admin.bookings.approve');

Route::get('/booking-history', [HomeController::class, 'bookingHistory'])->name('BookingHistory.show');


use App\Http\Controllers\BookingHistoryController;

Route::get('/booking-history', [BookingHistoryController::class, 'index'])
    ->name('BookingHistory.index')
    ->middleware('auth');
Route::delete('/booking/{id}/cancel', [BookingHistoryController::class, 'cancel'])
    ->name('booking.cancel')
    ->middleware('auth');

Route::view('/terms', 'home.Terms&Condition')->name('terms');


Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/customers/{id}/bookings', [AdminController::class, 'viewBookings'])
        ->name('admin.customers.bookings');
});
use App\Http\Controllers\ProfileController;

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});
Route::post('/bookings/{booking}/review', [ReviewController::class, 'store'])
    ->middleware('auth')
    ->name('reviews.store');
Route::post('/reviews/{booking}', [HomeController::class, 'storeReview'])->name('reviews.store')->middleware('auth');

Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    // other admin routes
    Route::get('/audit-logs', [AdminController::class, 'auditLogs'])->name('audit.logs');
});
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('/reviews', [ReviewController::class, 'adminIndex'])->name('reviews');
});
