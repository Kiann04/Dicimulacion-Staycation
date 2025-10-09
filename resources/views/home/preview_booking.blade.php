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
        max-width: 600px;
        margin: 2rem auto;
    }
    .booking-card h3 {
        color: #2c3e50;
        text-align: center;
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

<div class="booking-card">
    <h3>Confirm Your Booking</h3>

    @php
        use Carbon\Carbon;

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $nights = $end->lessThanOrEqualTo($start) ? 1 : $start->diffInDays($end);

        // Base price calculation
        $basePrice = $staycation->house_price * $nights;
        $extraGuests = max(0, $guest_number - 6);
        $extraFee = $extraGuests * 500;
        $basePrice += $extraFee;

        // VAT 12%
        $vatAmount = round($basePrice * 0.12, 2);

        // Total price with VAT
        $totalPrice = round($basePrice + $vatAmount, 2);

        // Half payment amount
        $halfPayment = round($totalPrice / 2, 2);

        $formattedStart = $start->format('M d, Y');
        $formattedEnd = $end->format('M d, Y');
    @endphp

    <div class="booking-info mb-4">
        <p><strong>Staycation:</strong> {{ $staycation->house_name }}</p>
        <p><strong>Guests:</strong> {{ $guest_number }}</p>
        <p><strong>Stay Dates:</strong> {{ $formattedStart }} - {{ $formattedEnd }} ({{ $nights }} night{{ $nights>1 ? 's' : '' }})</p>
        <hr>
        <p><strong>Base Price (without VAT):</strong> ₱{{ number_format($basePrice, 2) }}</p>
        <p><strong>VAT (12%):</strong> ₱{{ number_format($vatAmount, 2) }}</p>
        <p class="total-amount"><strong>Total Price (with VAT):</strong> ₱{{ number_format($totalPrice, 2) }}</p>
    </div>

    <form action="{{ route('booking.submit', $staycation->id) }}" method="POST" enctype="multipart/form-data">
        @csrf

        <!-- Hidden fields -->
        <input type="hidden" name="guest_number" value="{{ $guest_number }}">
        <input type="hidden" name="startDate" value="{{ $startDate }}">
        <input type="hidden" name="endDate" value="{{ $endDate }}">
        <input type="hidden" name="phone" value="{{ $phone ?? Auth::user()->phone ?? old('phone') }}">
        <input type="hidden" name="totalPrice" value="{{ $totalPrice }}">
        <input type="hidden" name="basePrice" value="{{ $basePrice }}">
        <input type="hidden" name="vatAmount" value="{{ $vatAmount }}">

        <!-- Payment Option -->
        <div class="mb-3">
            <label class="form-label fw-semibold">Payment Option</label>
            <select name="payment_type" id="paymentType" class="form-select" required>
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

        <!-- Message to Admin -->
        <div class="mb-3">
            <label class="form-label fw-semibold">Message to Admin (Optional)</label>
            <textarea name="message" class="form-control" rows="3" placeholder="Any special requests or notes"></textarea>
        </div>

        <p class="total-amount" id="amountDue">Amount Due: ₱{{ number_format($totalPrice, 2) }}</p>

        <button type="submit" class="btn-modern w-100 mt-3">Submit Booking Request</button>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const paymentSelect = document.getElementById("paymentType");
    const totalPrice = parseFloat("{{ $totalPrice }}");
    const amountDueElem = document.getElementById("amountDue");

    paymentSelect.addEventListener("change", function() {
        if(this.value === "half") {
            const half = (totalPrice / 2).toFixed(2);
            amountDueElem.textContent = "Amount Due (50%): ₱" + Number(half).toLocaleString();
        } else {
            amountDueElem.textContent = "Amount Due: ₱" + totalPrice.toLocaleString(undefined, {minimumFractionDigits:2});
        }
    });

    const methodSelect = document.getElementById("paymentMethod");
    const gcashInfo = document.getElementById("gcashInfo");
    const bpiInfo = document.getElementById("bpiInfo");

    methodSelect.addEventListener("change", function() {
        gcashInfo.style.display = this.value === "gcash" ? "block" : "none";
        bpiInfo.style.display = this.value === "bpi" ? "block" : "none";
    });
});
</script>
@endsection

@section('Footer')
    @include('Footer')
@endsection
