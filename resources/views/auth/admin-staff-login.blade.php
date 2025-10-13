
@extends('layouts.default')
@section('Header')
    @include('Header')
@endsection

<x-guest-layout>
    <!-- Bootstrap Login Section -->
    <section class="container d-flex align-items-center justify-content-center" style="min-height: 80vh;">
        <div class="row w-100 shadow rounded overflow-hidden" style="max-width: 900px;">
            
            <!-- Left: Login Form -->
            <div class="col-md-6 bg-white p-5">
                <h2 class="fw-bold mb-3 text-center">Admin Login</h2>
                <p class="text-muted text-center mb-4">Log in with your credentials</p>

                <form method="POST" action="{{ route('admin.staff.login') }}">
                    @csrf

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" 
                                required>
                        @error('email')
                            <p class="text-danger small">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <input type="password" name="password" id="password" class="form-control" 
                                required>
                        @error('password')
                            <p class="text-danger small">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary w-100">Log In</button>

                    <!-- Forgot Password -->
                    @if (Route::has('password.request'))
                        <div class="text-center mt-3">
                            <a href="{{ route('password.request') }}" class="small text-decoration-none">Forgot Password?</a>
                        </div>
                    @endif
                </form>
            </div>

            <!-- Right: Image -->
            <div class="col-md-6 d-none d-md-flex align-items-center justify-content-center bg-light">
                <img src="{{ asset('assets/HomeSticker.png') }}" alt="Home Sticker" class="img-fluid p-4">
            </div>
        </div>
    </section>
</x-guest-layout>

