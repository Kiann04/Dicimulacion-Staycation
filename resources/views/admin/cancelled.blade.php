@extends('layouts.default')
@section('Aside')
    @include('Aside')
@endsection
@section('content')
<body class="admin-dashboard">
  <div class="content-wrapper">
    <div class="main-content">
    <h3>Cancelled Bookings</h3>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-responsive mt-3">
        <table class="table table-bordered table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Staycation</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Total Price</th>
                    <th>Payment Status</th>
                    <th>Cancelled At</th>
                    <th>Proof of Payment</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cancelledBookings as $booking)
                    <tr>
                        <td>{{ $booking->id }}</td>
                        <td>{{ $booking->name }}</td>
                        <td>{{ $booking->staycation->house_name ?? 'N/A' }}</td>
                        <td>{{ $booking->formatted_start_date }}</td>
                        <td>{{ $booking->formatted_end_date }}</td>
                        <td>₱{{ number_format($booking->total_price, 2) }}</td>
                        <td>{{ ucfirst($booking->payment_status) }}</td>
                        <td>{{ \Carbon\Carbon::parse($booking->deleted_at)->format('M d, Y h:i A') }}</td>
                        <td>
                            @if($booking->payment_proof)
                            <a href="{{ asset('payment_proofs/' . basename($booking->payment_proof)) }}" 
                                target="_blank">View Proof</a>
                            @else
                            <span class="text-muted">No proof</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted">No cancelled bookings found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
