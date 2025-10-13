@extends('layouts.default')

@section('Header')
    @include('Header')
@endsection

<link rel="stylesheet" href="{{ asset('css/homepage.css') }}">

<style>
    .auth-card {
        max-width: 900px;
        margin: 80px auto;
        border-radius: 1rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .auth-form {
        padding: 2.5rem;
    }

    .object-fit-cover {
        object-fit: cover;
        object-position: center;
    }

    .form-label {
        font-weight: 600;
    }

    .btn-primary {
        background-color: #0d6efd;
        border: none;
    }

    .btn-primary:hover {
        background-color: #0b5ed7;
    }
</style>

<div class="container">
    <div class="row auth-card bg-white">
        <!-- Left side: Form -->
        <div class="col-md-6 d-flex flex-column justify-content-center auth-form">
            <h2 class="fw-bold mb-3 text-center">Two-Factor Authentication</h2>
            <p class="text-center text-muted mb-4">Enter the 6-digit code from your authenticator app or use a recovery code.</p>

            @if ($errors->any())
                <div class="alert alert-danger py-2">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('two-factor.login') }}">
                @csrf
                <div class="mb-3">
                    <label for="code" class="form-label">Authentication Code</label>
                    <input type="text" name="code" id="code" class="form-control" placeholder="123456" autofocus>
                </div>

                <div class="mb-4">
                    <label for="recovery_code" class="form-label">Or Recovery Code</label>
                    <input type="text" name="recovery_code" id="recovery_code" class="form-control" placeholder="Recovery code">
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2">Verify</button>
            </form>
        </div>

        <!-- Right side: Image -->
        <div class="col-md-6 d-none d-md-block p-0">
            <img src="{{ asset('assets/HomeSticker.png') }}" 
                 alt="Authentication Illustration" 
                 class="img-fluid h-100 w-100 object-fit-cover">
        </div>
    </div>
</div>

@section('Footer')
    @include('Footer')
@endsection
