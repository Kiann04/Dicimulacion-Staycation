@extends('layouts.default')

@section('Aside')
    @include('staff.StaffSidebar')
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/update-booking.css') }}">
@endpush
<body class="admin-dashboard">
<div class="update-booking-page">
    <h2>Update Booking</h2>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="alert success">{{ session('success') }}</div>
    @endif

    {{-- Error Messages --}}
    @if($errors->any())
        <div class="alert error">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ url('admin/update_booking/'.$booking->id) }}" method="POST">
        @csrf
        @method('PUT')

        
        <input type="hidden" name="staycation_id" value="{{ $booking->staycation_id }}">
        {{-- Customer Name --}}
        <label>Customer Name</label>
        <input type="text" name="name" value="{{ old('name', $booking->name) }}" required>

        {{-- Phone --}}
        <label>Phone</label>
        <input type="text" name="phone" value="{{ old('phone', $booking->phone) }}" required>

        {{-- Guest Number --}}
        <label>Guest Number</label>
        <input type="number" name="guest_number" value="{{ old('guest_number', $booking->guest_number) }}" required>

        {{-- Check-In Date --}}
        <label>Arrival Date</label>
        <input type="date" name="start_date" value="{{ old('start_date', $booking->start_date) }}" required>

        {{-- Check-Out Date --}}
        <label>Departure Date</label>
        <input type="date" name="end_date" value="{{ old('end_date', $booking->end_date) }}" required>

        <div class="buttons">
            <a href="{{ url('admin/bookings') }}" class="btn secondary">⬅ Back</a>
            <button type="submit" class="btn primary">💾 Save Changes</button>
        </div>
    </form>
</div>
