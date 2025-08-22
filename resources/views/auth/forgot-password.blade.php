@extends ('layouts.default');

@section ('Header')
@include ('Header')
@endsection
<x-guest-layout>
    <div class="login container">
        <div class="login-container">
            <h2>Forgot Password?</h2> 
            <p>Enter your email and we’ll send you a reset link</p>

            <!-- Show success message -->
            @if (session('status'))
                <div class="mb-4 font-medium text-sm text-green-600">
                    {{ session('status') }}
                </div>
            @endif

            <!-- Show validation errors -->
            <x-validation-errors class="mb-4" />

            <!-- Forgot Password Form -->
            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <span>Enter your email address</span>
                <input type="email" name="email" placeholder="yourmail@gmail.com" value="{{ old('email') }}" required>

                <input type="submit" class="buttom" value="Send Reset Link">
            </form>
        </div>

        <div class="login-image">
            <img src="{{ asset('Assets/ForgotSticker.png') }}" alt="">
        </div>
    </div>
</x-guest-layout>
@section ('Footer')
@include ('Footer')
@endsection