@extends('layouts.default')

@section('Header')
@include('Header')
@endsection

<x-guest-layout>
<link rel="stylesheet" href="{{ asset('css/homepage.css') }}">

<div class="login container">
    <div class="login-container">
        <h2>Create an Account</h2>
        <p>Fill in your details to register</p>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <span>Enter your name</span>
            <input type="text" name="name" placeholder="Your Full Name" required autofocus>

            <span>Enter your email address</span>
            <input type="email" name="email" placeholder="yourmail@gmail.com" required>

            <span>Enter your password</span>
            <div class="password-wrapper">
                <input type="password" id="password" name="password" placeholder="Password" required autocomplete="new-password">
                <button type="button" class="togglePassword">Show</button>
            </div>
            <p id="password-strength" style="font-size:0.9em; color: gray;">Password must be at least 8 characters, include letters and numbers.</p>
            @error('password')
                <p style="color:red; font-size:0.9em;">{{ $message }}</p>
            @enderror

            <span>Confirm your password</span>
            <div class="password-wrapper">
                <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Confirm Password" required>
                <button type="button" class="togglePassword">Show</button>
            </div>

            <input type="submit" value="Sign Up" class="buttom">
        </form>

        <a href="{{ route('login') }}" class="btn">Already have an account? Log in</a>
    </div>

    <div class="login-image">
        <img src="{{ asset('assets/HomeSticker.png') }}" alt="Home Sticker">
    </div>
</div>

<!-- Show/Hide & Strength Script -->
<script>
const password = document.querySelector('#password');
const passwordConfirmation = document.querySelector('#password_confirmation');
const toggleButtons = document.querySelectorAll('.togglePassword');
const strengthText = document.querySelector('#password-strength');

// Toggle both password fields
toggleButtons.forEach(button => {
    button.addEventListener('click', function() {
        const input = this.previousElementSibling; // the related input
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
        this.textContent = type === 'password' ? 'Show' : 'Hide';
    });
});

// Password strength for main password
password.addEventListener('input', function() {
    const val = password.value;
    const hasLetters = /[a-zA-Z]/.test(val);
    const hasNumbers = /[0-9]/.test(val);

    if(val.length >= 8 && hasLetters && hasNumbers){
        strengthText.textContent = "Strong password ✅";
        strengthText.style.color = "green";
    } else {
        strengthText.textContent = "Password must be at least 8 characters, include letters and numbers.";
        strengthText.style.color = "red";
    }
});
</script>
</x-guest-layout>

@section('Footer')
@include('Footer')
@endsection
