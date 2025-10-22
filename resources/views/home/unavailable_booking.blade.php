@extends('layouts.default')

@section('content')
<section class="container my-5 pt-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold text-danger">⚠️ Booking Unavailable</h2>
        <p class="text-muted">
            Sorry, <strong>{{ $staycation->house_name }}</strong> is not available from 
            <strong>{{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }}</strong> 
            to <strong>{{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</strong>.
        </p>
        <p class="fw-semibold">But don’t worry — here are some other staycations you might love:</p>
    </div>

    <div class="row g-4">
        @forelse($availableStaycations as $alt)
            <div class="col-md-4 d-flex">
                <div class="card shadow-sm border-0 flex-fill">
                    <img src="{{ asset($alt->house_image) }}" class="card-img-top rounded-top" style="height: 200px; object-fit: cover;">
                    <div class="card-body">
                        <h5 class="card-title fw-bold">{{ $alt->house_name }}</h5>
                        <p class="text-muted small mb-1">{{ $alt->house_location }}</p>
                        <p class="fw-semibold text-success mb-2">₱{{ number_format($alt->house_price, 2) }} / night</p>
                        <a href="{{ route('BookingHistory.bookingForm', $alt->id) }}" 
                           class="btn btn-primary w-100 fw-semibold">
                           Book This Staycation
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center">
                <p class="text-muted">No other available staycations at the moment.</p>
            </div>
        @endforelse
    </div>
</section>
@endsection
