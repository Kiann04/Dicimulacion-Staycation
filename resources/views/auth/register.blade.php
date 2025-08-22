@extends ('layouts.default');

@section ('Header')
@include ('Header')
@endsection

<x-guest-layout>
    <!-- Link your CSS -->
    <link rel="stylesheet" href="{{ asset('css/homepage.css') }}">

    <div class="login container">
        <div class="login-container">
            <h2>Create an Account</h2> 
            <p>Fill in your details to register</p>

            <!-- Laravel Register Form -->
            <form method="POST" action="{{ route('register') }}">
                @csrf

                <span>Enter your name</span>
                <input type="text" name="name" placeholder="Your Full Name" required autofocus>

                <span>Enter your email address</span>
                <input type="email" name="email" placeholder="yourmail@gmail.com" required>

                <span>Enter your password</span>
                <input type="password" name="password" placeholder="Password" required>

                <span>Confirm your password</span>
                <input type="password" name="password_confirmation" placeholder="Confirm Password" required>

                <input type="submit" value="Sign Up" class="buttom">
            </form>

            <!-- Link to Login -->
            <a href="{{ route('login') }}" class="btn">Already have an account? Log in</a>
        </div>

        <div class="login-image">
            <img src="{{ asset('Assets/HomeSticker.png') }}" alt="">
        </div>
    </div>
</x-guest-layout>

@section ('Footer')
@include ('Footer')
@endsection