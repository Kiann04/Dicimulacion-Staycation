@extends('layouts.default')

@section('Aside')
    @include('Aside')
@endsection

<style>
    .update-booking-container{
        display:flex;justify-content:center;align-items:center;
        min-height:100vh;background:#f9fafb;padding:1rem
    }
    .update-booking-card{
        background:#fff;padding:2rem;width:100%;max-width:500px;
        border-radius:1rem;box-shadow:0 5px 15px rgba(0,0,0,.1)
    }
    .update-booking-card h2{
        text-align:center;margin-bottom:1rem;font-weight:700;color:#333
    }
    label{display:block;font-weight:600;margin:.7rem 0 .3rem;color:#444}
    input[type=text],input[type=number],input[type=date]{
        width:100%;padding:.7rem 1rem;border:1px solid #ccc;
        border-radius:.5rem;font-size:1rem;background:#fefefe;
        transition:.2s
    }
    input:focus{
        border-color:#2563eb;outline:none;
        box-shadow:0 0 0 3px rgba(37,99,235,.15)
    }
    .alert{
        padding:.8rem 1rem;border-radius:.5rem;
        margin-bottom:1rem;font-size:.95rem
    }
    .success{background:#dcfce7;color:#166534;border:1px solid #bbf7d0}
    .error{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
    .error ul{margin:0;padding-left:1.2rem}
    .form-actions{
        display:flex;gap:.8rem;justify-content:space-between;
        margin-top:1.5rem;flex-wrap:wrap
    }
    .btn{
        flex:1;text-align:center;padding:.7rem 1rem;font-weight:600;
        border:none;border-radius:.5rem;cursor:pointer;text-decoration:none
    }
    .btn-primary{background:#2563eb;color:#fff}
    .btn-primary:hover{background:#1e40af}
    .btn-secondary{background:#e5e7eb;color:#333}
    .btn-secondary:hover{background:#d1d5db}
</style>
<body class="admin-dashboard">
<div class="content-wrapper">
<div class="update-booking-container">
    <div class="update-booking-card">
        <h2>✏️ Update Booking</h2>

        @if(session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif

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

            <label>Customer Name</label>
            <input type="text" name="name" value="{{ old('name', $booking->name) }}" required>

            <label>Phone</label>
            <input type="text" name="phone" value="{{ old('phone', $booking->phone) }}" required>

            <label>Guest Number</label>
            <input type="number" name="guest_number" value="{{ old('guest_number', $booking->guest_number) }}" required>

            <label>Check-In Date</label>
            <input type="date" name="start_date" value="{{ old('start_date', $booking->start_date) }}" required>

            <label>Check-Out Date</label>
            <input type="date" name="end_date" value="{{ old('end_date', $booking->end_date) }}" required>

            <div class="form-actions">
                <a href="{{ url('admin/bookings') }}" class="btn btn-secondary">⬅ Back</a>
                <button type="submit" class="btn btn-primary">💾 Save Changes</button>
            </div>
        </form>
    </div>
</div>
