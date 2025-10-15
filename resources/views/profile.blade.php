@extends('layouts.default')

@section('Header')
    @include('Header')
@endsection

<link rel="stylesheet" href="{{ asset('Css/home.css') }}">

<main style="margin-top: 30px;">
<div class="container py-5">
    <div class="row g-4">

        <!-- Profile Update -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Update Profile</h5>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="photo" class="form-label">Profile Photo</label>
                            <input type="file" name="photo" id="photo" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" name="name" id="name" 
                                   value="{{ old('name', auth()->user()->name) }}" 
                                   class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" 
                                   value="{{ old('email', auth()->user()->email) }}" 
                                   class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Change Password -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    @if (session('password_success'))
                        <div class="alert alert-success">{{ session('password_success') }}</div>
                    @endif

                    <form method="POST" action="{{ route('profile.password.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" name="current_password" id="current_password" 
                                class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" name="password" id="new_password" 
                                class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirm New Password</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" 
                                class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-warning w-100">Update Password</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Two-Factor Authentication -->
        <!-- Two-Factor Authentication -->
<div class="col-12">
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header bg-success text-white rounded-top-4">
            <h5 class="mb-0">Two-Factor Authentication</h5>
        </div>
        <div class="card-body">
            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            @if (auth()->user()->two_factor_secret)
                <div class="d-flex align-items-center mb-4">
                    <i class="bi bi-check-circle-fill text-success fs-3 me-2"></i>
                    <p class="mb-0 fw-bold">2FA is currently ENABLED for your account.</p>
                </div>

                <div class="row g-4 mb-4">
                    <!-- QR Code -->
                    <div class="col-md-6 text-center">
                        <h6 class="fw-semibold">Scan this QR Code:</h6>
                        <div class="my-2">{!! auth()->user()->twoFactorQrCodeSvg() !!}</div>
                        <small class="text-muted">Use your Authenticator App</small>
                    </div>

                    <!-- Recovery Codes -->
                    <div class="col-md-6">
                        <h6 class="fw-semibold">Recovery Codes:</h6>
                        <div class="d-grid gap-2">
                            @foreach (json_decode(decrypt(auth()->user()->two_factor_recovery_codes), true) as $code)
                                <input type="text" class="form-control text-center" value="{{ $code }}" readonly>
                            @endforeach
                        </div>
                        <small class="text-muted">Keep these safe for backup</small>
                    </div>
                </div>

                <form method="POST" action="{{ url('user/two-factor-authentication') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger w-100 fw-bold">Disable 2FA</button>
                </form>

            @else
                <div class="d-flex align-items-center mb-4">
                    <i class="bi bi-x-circle-fill text-danger fs-3 me-2"></i>
                    <p class="mb-0 fw-bold">2FA is currently DISABLED for your account.</p>
                </div>

                <form method="POST" action="{{ url('user/two-factor-authentication') }}">
                    @csrf
                    <button type="submit" class="btn btn-success w-100 fw-bold">Enable 2FA</button>
                </form>
            @endif

            <!-- Instructions -->
            <div class="mt-4 p-3 bg-light rounded-3">
                <h6 class="fw-semibold">How to Use 2FA:</h6>
                <ol class="mb-0 ps-3">
                    @if(!auth()->user()->two_factor_secret)
                        <li>Click <strong>Enable 2FA</strong> to set up.</li>
                        <li>Scan the QR code with your Authenticator App.</li>
                        <li>Save the recovery codes in a safe place.</li>
                        <li>Next time you log in, you’ll be asked for the 6-digit code.</li>
                    @else
                        <li>Scan the QR code in your Authenticator App if you haven’t already.</li>
                        <li>Use the 6-digit code from the app when logging in.</li>
                        <li>Save the recovery codes for backup access.</li>
                        <li>Click <strong>Disable 2FA</strong> if you want to turn it off.</li>
                    @endif
                </ol>
            </div>
        </div>
    </div>
</div>

    </div>
</div>
</main>

@section('Footer')
    @include('Footer')
@endsection
