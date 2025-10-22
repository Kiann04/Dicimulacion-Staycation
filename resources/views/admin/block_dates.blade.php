@extends('layouts.default')

@section('content')
<div class="container mt-5">
    <h2>Block a Date</h2>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <form action="{{ route('admin.blocked_dates.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="date" class="form-label">Date to Block</label>
            <input type="date" name="date" id="date" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="reason" class="form-label">Reason (optional)</label>
            <input type="text" name="reason" id="reason" class="form-control">
        </div>
        <button type="submit" class="btn btn-danger">Block Date</button>
    </form>

    <hr>
    <h4>Blocked Dates</h4>
    <ul>
        @foreach ($blockedDates as $blocked)
            <li>{{ $blocked->date }} - {{ $blocked->reason ?? 'No reason' }}</li>
        @endforeach
    </ul>
</div>
@endsection
