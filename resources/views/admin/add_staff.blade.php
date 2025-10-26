@extends('layouts.default')

@section('content')
<div class="container mt-4">
    <h3 class="fw-bold mb-4">Add Staff Account</h3>
    <form action="{{ route('admin.createStaff') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">Create Staff</button>
    </form>
</div>
@endsection
