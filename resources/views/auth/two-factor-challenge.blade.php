@extends ('layouts.default')

@section ('Header')
@include ('Header')
@endsection

<link rel="stylesheet" href="{{ asset('css/homepage.css') }}">

<div class="login container">
    <div class="login-container">
        <h2>Two-Factor Authentication</h2>
        <p>Enter the 6-digit code from your authenticator app or use a recovery code.</p>

        @if ($errors->any())
            <div class="alert alert-danger" style="color:red;">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('two-factor.login') }}">
            @csrf
            <span>Authentication Code</span>
            <input type="text" name="code" placeholder="123456" autofocus>

            <span>Or Recovery Code</span>
            <input type="text" name="recovery_code" placeholder="Recovery code">

            <input type="submit" value="Verify" class="buttom">
        </form>
    </div>

    <div class="login-image">
        <img src="{{ asset('assets/HomeSticker.png') }}" alt="">
    </div>
</div>

@section ('Footer')
@include ('Footer')
@endsection
