@extends('layouts.default')

@section('Header')
    @include('Header')
@endsection

@section('content')
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
</style>

<div class="container my-5">
    <div class="booking-card mx-auto" style="max-width:600px;">
        <h3 class="fw-bold mb-4 text-center">Confirm Your Booking</h3>

        @php
            use Carbon\Carbon;

            $start = Carbon::parse($startDate); // was $start_date
            $end = Carbon::parse($endDate);     // was $end_date

            // Prevent zero or negative nights
            $nights = $end->lessThanOrEqualTo($start) ? 1 : $start->diffInDays($end);

            // Base price
            $totalPrice = $staycation->house_price * $nights;

            // Extra guests
            $extraGuests = max(0, $guest_number - 6);
            $extraFee = $extraGuests * 500;
            $totalPrice += $extraFee;

            $formattedStart = $start->format('M d, Y');
            $formattedEnd = $end->format('M d, Y');
        @endphp

        <div class="booking-info mb-4">
            <p><strong>Staycation:</strong> {{ $staycation->house_name }}</p>
            <p><strong>Guests:</strong> {{ $guest_number }}</p>
            <p><strong>Stay Dates:</strong> {{ $formattedStart }} - {{ $formattedEnd }} ({{ $nights }} night{{ $nights>1 ? 's' : '' }})</p>
            <hr>
            @if($extraGuests > 0)
                <p class="total-amount">
                    Total Price: ₱{{ number_format($totalPrice, 2) }}<br>
                    <small class="text-muted">(Includes ₱{{ number_format($extraFee, 2) }} extra for {{ $extraGuests }} additional guest{{ $extraGuests>1 ? 's' : '' }})</small>
                </p>
            @else
                <p class="total-amount">Total Price: ₱{{ number_format($totalPrice, 2) }}</p>
            @endif
        </div>

        <form action="{{ route('booking.submit', $staycation->id) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- Hidden fields -->
            <input type="hidden" name="guest_number" value="{{ $guest_number }}">
            <input type="hidden" name="startDate" value="{{ $startDate }}">
            <input type="hidden" name="endDate" value="{{ $endDate }}">
            <input type="hidden" name="phone" value="{{ $phone ?? Auth::user()->phone ?? old('phone') }}">
            <input type="hidden" name="totalPrice" value="{{ $totalPrice }}">

            <!-- Payment Option -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Payment Option</label>
                <select name="payment_type" class="form-select" required>
                    <option value="">Select option</option>
                    <option value="half">Half Payment (50%)</option>
                    <option value="full">Full Payment</option>
                </select>
            </div>

            <!-- Payment Method -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Payment Method</label>
                <select name="payment_method" id="paymentMethod" class="form-select" required>
                    <option value="">Select method</option>
                    <option value="gcash">GCash</option>
                    <option value="bpi">BPI Bank Transfer</option>
                </select>
            </div>

            <!-- GCash Info -->
            <div id="gcashInfo" class="p-3 bg-light border rounded mb-3" style="display:none;">
                <h6 class="fw-semibold text-primary">GCash Information</h6>
                <p><strong>Account Name:</strong> Dicimulacion Staycation</p>
                <p><strong>GCash Number:</strong> 0917-123-4567</p>
            </div>

            <!-- BPI Info -->
            <div id="bpiInfo" class="p-3 bg-light border rounded mb-3" style="display:none;">
                <h6 class="fw-semibold text-primary">BPI Bank Information</h6>
                <p><strong>Account Name:</strong> Dicimulacion Staycation</p>
                <p><strong>Account Number:</strong> 1234-5678-90</p>
            </div>

            <!-- Upload Proof -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Upload Proof of Payment</label>
                <input type="file" name="payment_proof" class="form-control" accept="image/*" required>
            </div>

            <!-- Transaction Number -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Transaction Number (Optional)</label>
                <input type="text" name="transaction_number" class="form-control" placeholder="Enter transaction number">
            </div>

            <!-- Message to admin -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Message to Admin (Optional)</label>
                <textarea name="message" class="form-control" rows="3" placeholder="Any special requests or notes"></textarea>
            </div>

            <button type="submit" class="btn-modern w-100 mt-3">Submit Booking Request</button>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const paymentSelect = document.getElementById("paymentMethod");
    const gcashInfo = document.getElementById("gcashInfo");
    const bpiInfo = document.getElementById("bpiInfo");

    // Show/hide payment info
    paymentSelect.addEventListener("change", function() {
        gcashInfo.style.display = this.value === "gcash" ? "block" : "none";
        bpiInfo.style.display = this.value === "bpi" ? "block" : "none";
    });

    // Disable submit after click
    const form = document.querySelector("form");
    const submitButton = form.querySelector("button[type='submit']");
    form.addEventListener("submit", function() {
        submitButton.disabled = true;
        submitButton.textContent = "Submitting...";
    });

    // Update total for half payment
    const paymentType = form.querySelector("select[name='payment_type']");
    const totalPriceElem = document.querySelector(".total-amount");
    const totalPrice = parseFloat("{{ $totalPrice }}");

    paymentType.addEventListener("change", function() {
        if (this.value === "half") {
            totalPriceElem.textContent = "Amount Due (50%): ₱" + (totalPrice/2).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
        } else {
            totalPriceElem.textContent = "Total Price: ₱" + totalPrice.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
        }
    });
});
</script>

@endsection

@section('Footer')
    @include('Footer')
@endsection
