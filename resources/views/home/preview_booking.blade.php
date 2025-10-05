@extends('layouts.default')

@section('Header')
    @include('Header')
@endsection

@section('content') {{-- Wrap all main content in 'content' section --}}
<style>
    .booking-card {
        border-radius: 1rem;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        background: #fff;
        padding: 2rem;
        transition: transform 0.2s;
    }

    .booking-card:hover {
        transform: translateY(-5px);
    }

    .booking-card h3 {
        color: #2c3e50;
    }

    .booking-info p {
        font-size: 1rem;
        margin-bottom: 0.5rem;
    }

    .total-amount {
        font-size: 1.25rem;
        font-weight: 700;
        color: #007bff;
    }

    .btn-modern {
        background: linear-gradient(90deg,#007bff 0%,#00c6ff 100%);
        border: none;
        color: #fff;
        font-weight: 600;
        padding: 0.75rem;
        border-radius: 0.5rem;
        transition: 0.3s;
    }

    .btn-modern:hover {
        background: linear-gradient(90deg,#0056b3 0%,#00a4cc 100%);
    }

    .bank-info {
        transition: all 0.3s ease;
    }

    @media (max-width:576px) {
        .booking-card {
            padding: 1.5rem;
        }
    }
</style>

<div class="container my-5">
    <div class="booking-card mx-auto" style="max-width:600px;">
        <h3 class="fw-bold mb-4 text-center">Confirm Your Booking</h3>

        @php
            $vatRate = 0.12;

            $start = \Carbon\Carbon::parse($startDate);
            $end = \Carbon\Carbon::parse($endDate);

            // Number of nights (exclude departure)
            $nights = $end->diffInDays($start);

            // Price calculation
            $subtotal = $staycation->house_price * $nights;
            $vatAmount = $subtotal * $vatRate;
            $totalWithVat = $subtotal + $vatAmount;

            $formattedStart = $start->format('M d, Y');
            $formattedEnd = $end->format('M d, Y');
        @endphp

        <div class="booking-info mb-4">
            <p><strong>Staycation:</strong> {{ $staycation->house_name }}</p>
            <p><strong>Guests:</strong> {{ $guest_number }}</p>
            <p><strong>Stay Dates:</strong> {{ $formattedStart }} - {{ $formattedEnd }} ({{ $nights }} night{{ $nights>1?'s':'' }})</p>
            <hr>
            <p>Subtotal (Before VAT): ₱{{ number_format($subtotal, 2) }}</p>
            <p>VAT (12%): ₱{{ number_format($vatAmount, 2) }}</p>
            <p class="total-amount">Total: ₱{{ number_format($totalWithVat, 2) }}</p>
        </div>


        <form action="{{ route('booking.submit', $staycation->id) }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- Hidden Inputs --}}
            <input type="hidden" name="guest_number" value="{{ $guest_number }}">
            <input type="hidden" name="startDate" value="{{ $startDate }}">
            <input type="hidden" name="endDate" value="{{ $endDate }}">
            <input type="hidden" name="priceWithoutVat" value="{{ $priceWithoutVat }}">
            <input type="hidden" name="vatAmount" value="{{ $vatAmount }}">
            <input type="hidden" name="totalWithVat" value="{{ $totalWithVat }}">

            {{-- Payment Option --}}
            <div class="mb-3">
                <label class="form-label fw-semibold">Payment Option</label>
                <select name="payment_type" class="form-select" required>
                    <option value="">Select option</option>
                    <option value="half">Half Payment (50%)</option>
                    <option value="full">Full Payment</option>
                </select>
            </div>

            {{-- Payment Method --}}
            <div class="mb-3">
                <label class="form-label fw-semibold">Payment Method</label>
                <select name="payment_method" id="paymentMethod" class="form-select" required>
                    <option value="">Select method</option>
                    <option value="gcash">GCash</option>
                    <option value="bpi">BPI Bank Transfer</option>
                </select>
            </div>

            {{-- GCash Info --}}
            <div id="gcashInfo" class="bank-info p-3 bg-light border rounded mb-3" style="display:none;">
                <h6 class="fw-semibold text-primary">GCash Information</h6>
                <p class="mb-1"><strong>Account Name:</strong> Dicimulacion Staycation</p>
                <p class="mb-1"><strong>GCash Number:</strong> 0917-123-4567</p>
                <p class="mb-0 text-muted">Please upload your GCash payment screenshot below.</p>
            </div>

            {{-- BPI Info --}}
            <div id="bpiInfo" class="bank-info p-3 bg-light border rounded mb-3" style="display:none;">
                <h6 class="fw-semibold text-primary">BPI Bank Information</h6>
                <p class="mb-1"><strong>Account Name:</strong> Dicimulacion Staycation</p>
                <p class="mb-1"><strong>Account Number:</strong> 1234-5678-90</p>
                <p class="mb-0 text-muted">Please upload your deposit slip or transfer screenshot below.</p>
            </div>

            {{-- Proof of Payment --}}
            <div class="mb-3">
                <label class="form-label fw-semibold">Upload Proof of Payment</label>
                <input type="file" name="payment_proof" class="form-control" accept="image/*" required>
            </div>

            {{-- Transaction Number --}}
            <div class="mb-3">
                <label class="form-label fw-semibold">Transaction Number <span class="text-muted">(Optional)</span></label>
                <input type="text" name="transaction_number" class="form-control" placeholder="Enter transaction number">
            </div>

            {{-- Message to Admin --}}
            <div class="mb-3">
                <label class="form-label fw-semibold">Message to Admin <span class="text-muted">(Optional)</span></label>
                <textarea name="message" class="form-control" rows="3" placeholder="Any special notes..."></textarea>
            </div>

            <button type="submit" class="btn-modern w-100 mt-3">Submit Booking Request</button>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function(){
    const paymentSelect = document.getElementById("paymentMethod");
    const gcashInfo = document.getElementById("gcashInfo");
    const bpiInfo = document.getElementById("bpiInfo");

    paymentSelect.addEventListener("change", function(){
        gcashInfo.style.display = "none";
        bpiInfo.style.display = "none";
        if(this.value === "gcash") gcashInfo.style.display = "block";
        else if(this.value === "bpi") bpiInfo.style.display = "block";
    });
});
</script>

@endsection {{-- This ends the 'content' section --}}

@section('Footer')
    @include('Footer')
@endsection
