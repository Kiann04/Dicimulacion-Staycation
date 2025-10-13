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
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Two-Factor Authentication</h5>
                </div>
                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif

                    @if (auth()->user()->two_factor_secret)
                        <p>✅ 2FA is currently <strong>ENABLED</strong> for your account.</p>

                        <div class="mb-3">
                            <h6>Scan this QR Code with your Authenticator App:</h6>
                            <div>{!! auth()->user()->twoFactorQrCodeSvg() !!}</div>
                        </div>

                        <div class="mb-3">
                            <h6>Recovery Codes:</h6>
                            <ul class="list-group">
                                @foreach (json_decode(decrypt(auth()->user()->two_factor_recovery_codes), true) as $code)
                                    <li class="list-group-item">{{ $code }}</li>
                                @endforeach
                            </ul>
                        </div>

                        <form method="POST" action="{{ url('user/two-factor-authentication') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100">Disable 2FA</button>
                        </form>
                    @else
                        <p>❌ 2FA is currently <strong>DISABLED</strong> for your account.</p>
                        <form method="POST" action="{{ url('user/two-factor-authentication') }}">
                            @csrf
                            <button type="submit" class="btn btn-success w-100">Enable 2FA</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
</main>

@section('Footer')
    @include('Footer')
@endsection
