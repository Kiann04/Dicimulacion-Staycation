@extends('layouts.default')

@section('Header')
@include('Header')
@endsection

@section('content')
<link rel="stylesheet" href="{{ asset('css/homepage.css') }}">

<div class="login container">
    <div class="login-container">
        <h2>Two-Factor Authentication Settings</h2>

        @if (session('status'))
            <p style="color: green;">{{ session('status') }}</p>
        @endif

        {{-- 2FA ENABLED --}}
        @if (auth()->user()->two_factor_secret)
            <p>✅ 2FA is currently <strong>ENABLED</strong> for your account.</p>

            <h4>Scan this QR Code in your Authenticator App:</h4>
            <div>{!! auth()->user()->twoFactorQrCodeSvg() !!}</div>

            <h4>Recovery Codes:</h4>
            <ul>
                @foreach (json_decode(decrypt(auth()->user()->two_factor_recovery_codes), true) as $code)
                    <li>{{ $code }}</li>
                @endforeach
            </ul>

            <form method="POST" action="{{ url('user/two-factor-authentication') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="buttom" style="background:red;">Disable 2FA</button>
            </form>

        {{-- 2FA DISABLED --}}
        @else
            <p>❌ 2FA is currently <strong>DISABLED</strong> for your account.</p>

            <form method="POST" action="{{ url('user/two-factor-authentication') }}">
                @csrf
                <button type="submit" class="buttom" style="background:green;">Enable 2FA</button>
            </form>
        @endif
    </div>

    <div class="login-image">
        <img src="{{ asset('assets/HomeSticker.png') }}" alt="">
    </div>
</div>
@endsection

@section('Footer')
@include('Footer')
@endsection
