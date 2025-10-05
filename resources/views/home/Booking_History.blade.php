@extends('layouts.default')

@section('Header')
@include('Header')
@endsection

<div class="container my-5">
    <h3 class="fw-bold mb-4">Your Booking History</h3>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($bookings->count())
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Staycation</th>
                    <th>Dates</th>
                    <th>Guests</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bookings as $b)
                <tr>
                    <td>{{ $b->staycation->house_name }}</td>
                    <td>{{ $b->start_date }} - {{ $b->end_date }}</td>
                    <td>{{ $b->guest_number }}</td>
                    <td>₱{{ number_format($b->total_price,2) }}</td>
                    <td>{{ ucfirst($b->status) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>You have no bookings yet.</p>
    @endif
</div>

@section('Footer')
@include('Footer')
@endsection
