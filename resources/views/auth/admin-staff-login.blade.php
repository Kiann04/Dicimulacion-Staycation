@extends('layouts.default')

<x-guest-layout>
    <!-- Admin Login Section -->
    <section class="container d-flex align-items-center justify-content-center" style="min-height: 100vh; background: #f0f2f5;">
        <div class="row w-100 shadow-lg rounded overflow-hidden" style="max-width: 900px;">

            <!-- Left: Login Form -->
            <div class="col-md-6 bg-white p-5 position-relative">
                <!-- Admin Logo / Icon -->
                <div class="text-center mb-4">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width: 70px; height: 70px; font-size: 30px;">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h3 class="mt-2 fw-bold">Admin Panel</h3>
                    <p class="text-muted">Secure login to access dashboard</p>
                </div>

                <!-- Login Form -->
                <form method="POST" action="{{ route('admin.staff.login') }}">
                    @csrf

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control form-control-lg" placeholder="admin@example.com" required>
                        @error('email')
                            <p class="text-danger small mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <input type="password" name="password" id="password" class="form-control form-control-lg" placeholder="********" required>
                        @error('password')
                            <p class="text-danger small mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm">Sign In</button>

                    <!-- Forgot Password -->
                    @if (Route::has('password.request'))
                        <div class="text-center mt-3">
                            <a href="{{ route('password.request') }}" class="small text-decoration-none text-primary">Forgot Password?</a>
                        </div>
                    @endif
                </form>

                <!-- Footer note -->
                <div class="position-absolute bottom-3 text-center w-100">
                    <small class="text-muted">&copy; {{ date('Y') }} Admin Dashboard</small>
                </div>
            </div>

            <!-- Right: Dashboard Style Image -->
            <div class="col-md-6 d-none d-md-flex align-items-center justify-content-center bg-primary text-white position-relative">
                <img src="{{ asset('assets/HomeSticker.png') }}" alt="Home Sticker" class="img-fluid p-4" style="max-width: 80%; opacity: 0.9;">
                <div class="position-absolute bottom-4 text-center px-3">
                    <h4 class="fw-bold">Welcome Back, Admin!</h4>
                    <p class="small">Manage users, bookings, and all dashboard activities securely.</p>
                </div>
            </div>

        </div>
    </section>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
            border-color: #0d6efd;
        }
        .btn-primary {
            background: linear-gradient(135deg,#0d6efd,#6610f2);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg,#6610f2,#0d6efd);
        }
    </style>
</x-guest-layout>
