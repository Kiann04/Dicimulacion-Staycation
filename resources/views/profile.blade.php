@extends('layouts.default')

@section('Header')
    @include('Header')
@endsection

<link rel="stylesheet" href="{{ asset('Css/home.css') }}">

<div class="profile-container mt-header">
    <div class="profile-card">
        <div class="profile-header">
            <h4>Update Profile</h4>
        </div>

        <div class="profile-body">
            {{-- Success Message --}}
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Validation Errors --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="profile-form">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="photo">Profile Photo</label>
                    <input type="file" name="photo" id="photo">
                </div>

                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', auth()->user()->name) }}" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email', auth()->user()->email) }}" required>
                </div>

                <button type="submit" class="btn-submit">Update Profile</button>
            </form>
        </div>
    </div>
</div>

@section('Footer')
    @include('Footer')
@endsection
