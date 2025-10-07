@extends('layouts.default')

@section('Header')
@include('Header')
@endsection

<section class="container min-vh-100 d-flex justify-content-center align-items-center">
    <div class="row justify-content-center align-items-center w-100">
        <!-- Form Column -->
        <div class="col-lg-5 col-md-7 mb-4 mb-lg-0">
            <div class="card shadow-lg p-4 rounded-4">
                <h2 class="fw-bold text-center mb-3">Forgot Password?</h2>
                <p class="text-center text-muted mb-4">Enter your email and new password</p>

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="{{ old('email', $request->email) }}" placeholder="Email" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="password" placeholder="Password" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="password_confirmation" placeholder="Confirm Password" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold rounded-3">Reset Password</button>
                </form>
            </div>
        </div>

        <!-- Image Column -->
        <div class="col-lg-5 d-none d-lg-flex justify-content-center">
            <img src="{{ asset('assets/ForgotSticker.png') }}" class="img-fluid" alt="Forgot Password Illustration">
        </div>
    </div>
</section>

@section('Footer')
@include('Footer')
@endsection
