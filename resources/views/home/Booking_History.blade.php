@extends('layouts.default')

@section('Header')
    @include('Header')
@endsection

@section('content')
<div class="container my-5">
    <h3 class="fw-bold mb-4 text-center">Your Booking History</h3>

    {{-- Success message --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @forelse($bookings as $booking)
        <div class="card mb-4 shadow-sm border-0 rounded-4">
            <div class="card-body">
                <h5 class="card-title">{{ $booking->staycation->house_name }}</h5>
                <p class="mb-1"><strong>Guests:</strong> {{ $booking->guest_number }}</p>
                <p class="mb-1"><strong>Dates:</strong> {{ $booking->start_date }} - {{ $booking->end_date }}</p>
                <p class="mb-1"><strong>Total:</strong> ₱{{ number_format($booking->total_price, 2) }}</p>
                <p class="mb-1"><strong>Payment Status:</strong> 
                    @if($booking->payment_status === 'paid') 
                        <span class="text-success">Paid</span>
                    @elseif($booking->payment_status === 'half_paid') 
                        <span class="text-warning">Half Paid</span>
                    @else 
                        <span class="text-secondary">Pending</span>
                    @endif
                </p>
                <p class="mb-1"><strong>Status:</strong> 
                    @if($booking->status === 'pending')
                        <span class="text-secondary">Pending</span>
                    @elseif($booking->status === 'confirmed')
                        <span class="text-success">Confirmed</span>
                    @elseif($booking->status === 'cancelled')
                        <span class="text-danger">Cancelled</span>
                    @endif
                </p>

                {{-- Cancel button if pending --}}
                @if($booking->status === 'pending')
                    <form action="{{ route('BookingHistory.cancel', $booking->id) }}" method="POST" class="mt-2">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Cancel Booking</button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <p class="text-center text-muted">You have no bookings yet.</p>
    @endforelse
</div>
@endsection

@section('Footer')
    @include('Footer')
@endsection
