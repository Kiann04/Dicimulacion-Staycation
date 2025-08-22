<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\StaycationController;
use App\Http\Controllers\HomeController;


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

// Authentication views (optional if using Laravel Jetstream)
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

// Handle login request
Route::post('/login', [LoginController::class, 'login'])->name('login.perform');

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
