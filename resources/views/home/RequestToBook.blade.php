@extends('layouts.default')

@section('Header')
@include('Header')
@endsection

<div class="container my-5">
    <h3 class="fw-bold">Confirm Your Booking</h3>
    <p>Staycation: {{ $staycation->house_name }}</p>
    <p>Total Amount: <strong>₱{{ number_format($totalWithVat, 2) }}</strong></p>

    <form action="{{ route('booking.submit', $staycation->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="guest_number" value="{{ $guest_number }}">
        <input type="hidden" name="startDate" value="{{ $startDate }}">
        <input type="hidden" name="endDate" value="{{ $endDate }}">

        <div class="mb-3">
            <label class="form-label">Payment Option</label>
            <select name="payment_type" class="form-select" required>
                <option value="">Select option</option>
                <option value="half">Half Payment (50%)</option>
                <option value="full">Full Payment</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Payment Method</label>
            <select name="payment_method" class="form-select" required>
                <option value="">Select bank</option>
                <option value="gcash">GCash - 09123456789</option>
                <option value="bpi">BPI - 1234-5678-90</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Upload Proof of Payment</label>
            <input type="file" name="payment_proof" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Transaction Number (Optional)</label>
            <input type="text" name="transaction_number" class="form-control" placeholder="Enter transaction number">
        </div>

        <div class="mb-3">
            <label class="form-label">Message to Admin (Optional)</label>
            <textarea name="message" class="form-control" rows="3" placeholder="Any special notes..."></textarea>
        </div>

        <button type="submit" class="btn btn-primary w-100">Submit Booking Request</button>
    </form>
</div>

@section('Footer')
@include('Footer')
@endsection
