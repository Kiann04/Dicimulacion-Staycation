@extends('layouts.default')

@section('Header')
    @include('Header')
@endsection

<link rel="stylesheet" href="{{ asset('Css/home.css') }}">

<div class="container py-5">
    <div class="row g-4">

        <!-- Profile Update -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Update Profile</h5>
                </div>
                <div class="card-body">
                    @if (session('profile_success'))
                        <div class="alert alert-success">{{ session('profile_success') }}</div>
                    @endif

                    @if ($errors->getBag('profile')->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->getBag('profile')->all() as $error)
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
                            <input type="file" name="photo" id="photo" 
                                class="form-control @error('photo', 'profile') is-invalid @enderror">
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" name="name" id="name" 
                                value="{{ old('name', auth()->user()->name) }}" 
                                class="form-control @error('name', 'profile') is-invalid @enderror" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" 
                                value="{{ old('email', auth()->user()->email) }}" 
                                class="form-control @error('email', 'profile') is-invalid @enderror" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Change Password -->
        <!-- Change Password -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Change Password</h5>
                </div>
                <div class="card-body">

                    <!-- Success message -->
                    @if (session('password_success'))
                        <div class="alert alert-success">{{ session('password_success') }}</div>
                    @endif
                    
                    <!-- Errors for password only -->
                    @if ($errors->getBag('password')->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->getBag('password')->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('profile.password.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" name="current_password" id="current_password" 
                                class="form-control @error('current_password','password') is-invalid @enderror" required>
                            @error('current_password','password')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" name="password" id="new_password" 
                                class="form-control @error('password','password') is-invalid @enderror" required>
                            @error('password','password')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
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
    </div>
</div>

@section('Footer')
    @include('Footer')
@endsection
