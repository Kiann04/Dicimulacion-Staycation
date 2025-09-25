@extends ('layouts.default');

@section ('Header')
@include ('Header')
@endsection
<x-guest-layout>
    <link rel="stylesheet" href="{{ asset('css/homepage.css') }}">

    <div class="login container">
        <div class="login-container">
            <h2>Admin Login</h2> 
            <p>Log in with your data that you entered<br>during your registration</p>

            <form method="POST" action="{{ route('admin.staff.login') }}">
                @csrf

                <span>Enter your email address</span>
                <input type="email" name="email" placeholder="yourmail@gmail.com" required>
                    @error('email')
                        <p class="error">{{ $message }}</p>
                    @enderror
                <span>Enter your password</span>
                <input type="password" name="password" placeholder="Password" required>
                    @error('password')
                        <p class="error">{{ $message }}</p>
                    @enderror
                <input type="submit" value="Log In" class="buttom">

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}">Forget Password?</a>
                @endif
            </form>

            @if (Route::has('register'))
                <a href="{{ route('register') }}" class="btn">Sign up now</a>
            @endif
        </div>

        <div class="login-image">
            <img src="{{ asset('assets/HomeSticker.png') }}" alt="">
        </div>
    </div>
</x-guest-layout>
@section ('Footer')
@include ('Footer')
@endsection