@extends ('layouts.default');

@section ('Header')
@include ('Header')
@endsection
<link rel="stylesheet" href="{{ asset('Css/Homepage.css') }}">
<div class="login container">
    <div class="login-container">
        <h2>Forgot Password?</h2>
        <p>Enter Email And Your New Password</p>

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <span>Enter your email</span>
            <input class="input-field" type="email" name="email" value="{{ old('email', $request->email) }}" placeholder="Email" required>

            <span>Enter your new password</span>
            <input class="input-field" type="password" name="password" placeholder="Password" required>

            <span>Re-enter your new password</span>
            <input class="input-field" type="password" name="password_confirmation" placeholder="Password" required>

            <input type="submit" class="buttom" value="Reset Password">
        </form>
    </div>

    <div class="login-image">
        <img src="{{ asset('assets/ForgotSticker.png') }}" alt="">
    </div>
</div>
@section ('Footer')
@include ('Footer')
@endsection