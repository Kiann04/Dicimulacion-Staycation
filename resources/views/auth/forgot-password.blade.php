@extends('layouts.default')

@section('Header')
@include('Header')
@endsection

<x-guest-layout>
<section class="container my-5">
    <div class="row justify-content-center align-items-center">
        <!-- Form Column -->
        <div class="col-lg-5 col-md-7 d-flex align-items-center">
            <div class="card shadow-lg p-4 rounded-4 w-100">
                <h2 class="fw-bold text-center mb-3">Forgot Password?</h2>
                <p class="text-center text-muted mb-4">Enter your email and we’ll send you a reset link</p>

                <!-- Success Message -->
                @if (session('status'))
                    <div class="alert alert-success mb-3">
                        {{ session('status') }}
                    </div>
                @endif

                <!-- Validation Errors -->
                <x-validation-errors class="mb-3" />

                <!-- Forgot Password Form -->
                <form method="POST" action="{{ route('password.email') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" name="email" placeholder="yourmail@gmail.com" value="{{ old('email') }}" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold rounded-3">Send Reset Link</button>
                </form>
            </div>
        </div>

        <!-- Image Column -->
        <div class="col-lg-5 d-flex justify-content-center">
            <img src="{{ asset('assets/ForgotSticker.png') }}" class="img-fluid" alt="Forgot Password Illustration" style="max-height: 500px; object-fit: contain;">
        </div>
    </div>
</section>
</x-guest-layout>

@section('Footer')
@include('Footer')
@endsection
