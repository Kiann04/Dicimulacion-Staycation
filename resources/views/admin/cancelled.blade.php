@extends('layouts.default')
@section('Aside')
    @include('Aside')
@endsection

@section('content')
<body class="admin-dashboard">
<div class="content-wrapper">
    <div class="main-content">
    <h2><i class="fa-solid fa-ban text-danger"></i> Cancelled Bookings</h2>
    <table class="table table-bordered mt-3">
        <thead>
            <tr>
                <th>ID</th>
                <th>Guest</th>
                <th>Staycation</th>
                <th>Dates</th>
                <th>Total</th>
                <th>Payment Status</th>
                <th>Proof</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cancelledBookings as $booking_history)
            <tr>
                <td>{{ $booking_history->id }}</td>
                <td>{{ $booking_history->name }}</td>
                <td>{{ $booking_history->staycation->name ?? 'N/A' }}</td>
                <td>{{ $booking_history->start_date->format('M d, Y') }} - {{ $booking_history->end_date->format('M d, Y') }}</td>
                <td>₱{{ number_format($booking_history->total_price, 2) }}</td>
                <td><span class="badge bg-danger">Cancelled</span></td>
                <td>
                    @if($booking_history->payment_proof)
                        <a href="{{ asset('storage/' . $booking_history->payment_proof) }}" target="_blank" class="btn btn-sm btn-outline-primary">View Proof</a>
                    @else
                        <span>No proof</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-muted">No cancelled bookings found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
