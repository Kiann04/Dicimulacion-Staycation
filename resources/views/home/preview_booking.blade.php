@extends('layouts.default')

@section('content')
<div class="container my-5">
    <div class="card shadow p-4 border-0">
        <h3 class="fw-bold mb-4 text-center">Review Your Booking Request</h3>

        <div class="mb-4">
            <p><strong>Staycation:</strong> {{ $staycation->house_name }}</p>
            <p><strong>Guest Name:</strong> {{ $name }}</p>
            <p><strong>Guests:</strong> {{ $guest_number }}</p>
            <p><strong>Check-in:</strong> {{ $startDate }}</p>
            <p><strong>Check-out:</strong> {{ $endDate }}</p>
            <p class="fw-bold text-success">Total Price: ₱{{ number_format($totalPrice, 2) }}</p>
        </div>

        <form action="{{ route('booking.request', $staycation->id) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- Payment Option -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Payment Option</label>
                <select name="payment_type" id="payment_type" class="form-select" required>
                    <option value="">-- Select Payment Option --</option>
                    <option value="half">Half Payment (50%)</option>
                    <option value="full">Full Payment</option>
                </select>
            </div>

            <!-- Payment Method -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Payment Method</label>
                <select name="payment_method" id="payment_method" class="form-select" required>
                    <option value="">-- Select Payment Method --</option>
                    <option value="gcash">GCash</option>
                    <option value="bpi">BPI</option>
                </select>
            </div>

            <!-- Payment Details (shows dynamically) -->
            <div id="gcash_details" class="border rounded p-3 mb-3 bg-light" style="display:none;">
                <p class="fw-bold mb-1">GCash Payment Details</p>
                <p class="mb-0">📱 GCash Number: <strong>0917 123 4567</strong></p>
                <p class="mb-0">Name: <strong>Dicimulacion Staycation</strong></p>
            </div>

            <div id="bpi_details" class="border rounded p-3 mb-3 bg-light" style="display:none;">
                <p class="fw-bold mb-1">BPI Bank Details</p>
                <p class="mb-0">🏦 Account Name: <strong>Dicimulacion Staycation</strong></p>
                <p class="mb-0">Account Number: <strong>1234-5678-90</strong></p>
            </div>

            <!-- Proof of Payment -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Upload Proof of Payment</label>
                <input type="file" name="payment_proof" class="form-control" required>
                <small class="text-muted">Upload screenshot or receipt of your payment.</small>
            </div>

            <!-- Transaction Number (optional) -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Transaction Number (optional)</label>
                <input type="text" name="transaction_number" class="form-control" placeholder="Enter transaction reference number">
            </div>

            <!-- Message to Admin -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Message to Admin (optional)</label>
                <textarea name="message" class="form-control" rows="3" placeholder="Write your message..."></textarea>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-success w-100 fw-semibold">Submit Booking Request</button>
        </form>
    </div>
</div>

<script>
document.getElementById('payment_method').addEventListener('change', function() {
    const gcash = document.getElementById('gcash_details');
    const bpi = document.getElementById('bpi_details');

    gcash.style.display = 'none';
    bpi.style.display = 'none';

    if (this.value === 'gcash') gcash.style.display = 'block';
    if (this.value === 'bpi') bpi.style.display = 'block';
});
</script>
@endsection
