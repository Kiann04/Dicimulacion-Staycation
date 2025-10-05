@extends('layouts.default')

@section('content')
<div class="container my-5">
    <div class="card shadow-sm p-4">
        <h3 class="fw-bold mb-3">Review Your Booking Request</h3>

        <p><strong>Staycation:</strong> {{ $staycation->house_name }}</p>
        <p><strong>Guest Name:</strong> {{ $name }}</p>
        <p><strong>Contact Number:</strong> {{ $phone }}</p>
        <p><strong>Guests:</strong> {{ $guest_number }}</p>
        <p><strong>Check-in:</strong> {{ $startDate }}</p>
        <p><strong>Check-out:</strong> {{ $endDate }}</p>
        <p><strong>Total Price:</strong> ₱{{ number_format($totalPrice, 2) }}</p>

        <form action="{{ route('booking.request', $staycation->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label class="form-label">Payment Option</label>
                <select name="payment_type" class="form-select" required>
                    <option value="half">Half Payment (50%)</option>
                    <option value="full">Full Payment</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Payment Method</label>
                <select name="payment_method" class="form-select" required>
                    <option value="gcash">GCash</option>
                    <option value="bank">Bank Transfer</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Upload Proof of Payment</label>
                <input type="file" name="payment_proof" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Message to Admin (optional)</label>
                <textarea name="message" class="form-control" rows="3" placeholder="Write a message to the admin..."></textarea>
            </div>

            <button type="submit" class="btn btn-success w-100 fw-semibold">
                Submit Booking Request
            </button>
        </form>
    </div>
</div>
@endsection
