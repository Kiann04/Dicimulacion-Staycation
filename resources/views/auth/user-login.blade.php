@extends('layouts.default')

@section('Header')
    @include('layouts.header')
@endsection

<x-guest-layout>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <div class="container d-flex align-items-center justify-content-center min-vh-100">
        <div class="row shadow-lg rounded-4 overflow-hidden bg-white" style="max-width: 900px; width: 100%;">
            
            <!-- Left side (form) -->
            <div class="col-md-6 p-5 d-flex flex-column justify-content-center">
                <h2 class="fw-bold mb-3">Welcome Back 👋</h2>
                <p class="text-muted mb-4">Please log in to continue to your account.</p>

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input id="email" type="email" name="email" class="form-control" placeholder="yourmail@gmail.com" required>
                        @error('email')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input id="password" type="password" name="password" class="form-control" placeholder="••••••••" required>
                        @error('password')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="small text-primary text-decoration-none">Forgot Password?</a>
                        @endif
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">Log In</button>

                    @if (Route::has('register'))
                        <p class="text-center mt-3 small">
                            Don’t have an account?
                            <a href="{{ route('register') }}" class="fw-semibold text-decoration-none">Sign up</a>
                        </p>
                    @endif
                </form>
            </div>

            <!-- Right side (image) -->
            <div class="col-md-6 d-none d-md-flex bg-light align-items-center justify-content-center">
                <img src="{{ asset('assets/HomeSticker.png') }}" class="img-fluid p-4" alt="Staycation">
            </div>
        </div>
    </div>
</x-guest-layout>

@section('Footer')
    @include('layouts.footer')
@endsection
