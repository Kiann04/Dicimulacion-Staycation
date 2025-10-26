@extends('layouts.default')

@section('Aside')
    @include('Aside')
@endsection
<body class="admin-dashboard">
  <div class="content-wrapper">
    <div class="main-content">
@section('content')
<div class="container my-5">
    <h2 class="fw-bold mb-4">Create Staff Account</h2>

    @if (session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm p-4 border-0 rounded-4">
        <form action="{{ route('admin.createStaff') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-user-plus"></i> Create Staff
            </button>
        </form>
    </div>
</div>
@endsection
