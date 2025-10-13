@extends('layouts.default')

@section('Header')
    @include('Header')
@endsection

@section('content')
<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="row w-100 shadow rounded-4 overflow-hidden" style="max-width: 850px;">
        <!-- Form Section -->
        <div class="col-md-6 bg-white p-5 d-flex flex-column justify-content-center">
            <h2 class="mb-3 fw-bold text-center">Two-Factor Authentication</h2>
            <p class="text-center text-muted mb-4">
                Enter the 6-digit code from your authenticator app or use a recovery code.
            </p>

            @if ($errors->any())
                <div class="alert alert-danger py-2">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('two-factor.login') }}">
                @csrf

                <div class="mb-3">
                    <label for="code" class="form-label fw-semibold">Authentication Code</label>
                    <input type="text" id="code" name="code" class="form-control form-control-lg" placeholder="123456" autofocus>
                </div>

                <div class="mb-4">
                    <label for="recovery_code" class="form-label fw-semibold">Or Recovery Code</label>
                    <input type="text" id="recovery_code" name="recovery_code" class="form-control form-control-lg" placeholder="Recovery code">
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fs-5">
                    Verify
                </button>
            </form>
        </div>

        <!-- Image Section -->
        <div class="col-md-6 d-none d-md-block p-0">
            <div class="h-100 w-100">
                <img src="{{ asset('assets/HomeSticker.png') }}" 
                    alt="Authentication Illustration" 
                    class="img-fluid h-100 w-100 object-fit-cover rounded-end">
            </div>
        </div>

    </div>
</div>
@endsection

@section('Footer')
    @include('Footer')
@endsection
