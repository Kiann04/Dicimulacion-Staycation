@extends('layouts.default')

@section('Header')
    @include('Header')
@endsection

<x-guest-layout>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <div class="container d-flex align-items-center justify-content-center min-vh-100">
        <div class="row shadow-lg rounded-4 overflow-hidden bg-white" style="max-width: 900px; width: 100%;">

            <!-- Left side (form) -->
            <div class="col-md-6 p-5 d-flex flex-column justify-content-center">
                <h2 class="fw-bold mb-3">Confirm Password 🔐</h2>
                <p class="text-muted mb-4">
                    This is a secure area of the application. Please confirm your password before continuing.
                </p>

                <form method="POST" action="{{ route('password.confirm') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input id="password" type="password" name="password" class="form-control" placeholder="••••••••" required autofocus>
                        @error('password')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">Confirm Password</button>
                </form>
            </div>

            <!-- Right side (image) -->
            <div class="col-md-6 d-none d-md-flex bg-light align-items-center justify-content-center">
                <img src="{{ asset('assets/HomeSticker.png') }}" class="img-fluid p-4" alt="Staycation">
            </div>
        </div>
    </div>
</x-guest-layout>

@section('Footer')
    @include('Footer')
@endsection
