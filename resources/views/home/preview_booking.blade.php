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
    .booking-card:hover { transform: translateY(-5px); }
    .booking-card h3 { color: #2c3e50; }
    .booking-info p { font-size: 1rem; margin-bottom: 0.5rem; }
    .total-amount { font-size: 1.25rem; font-weight: 700; color: #007bff; }
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

            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            $nights = $end->lessThanOrEqualTo($start) ? 1 : $start->diffInDays($end);

            // Base total price including extra guests
            $baseTotal = $staycation->house_price * $nights;
            $extraGuests = max(0, $guest_number - 6);
            $extraFee = $extraGuests * 500;
            $baseTotal += $extraFee;

            // VAT & Base Price
            $vatAmount = round($baseTotal - ($baseTotal / 1.12), 2);
            $basePrice = round($baseTotal - $vatAmount, 2);

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

            <p class="total-amount">
                <strong>Total Price (with VAT):</strong> ₱{{ number_format($baseTotal, 2) }}
            </p>
        </div>

        <form action="{{ route('booking.submit', $staycation->id) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- Hidden fields -->
            <input type="hidden" name="guest_number" value="{{ $guest_number }}">
            <input type="hidden" name="startDate" value="{{ $startDate }}">
            <input type="hidden" name="endDate" value="{{ $endDate }}">
            <input type="hidden" name="phone" value="{{ $phone ?? Auth::user()->phone ?? old('phone') }}">
            <input type="hidden" name="totalPrice" value="{{ $baseTotal }}">

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

            <!-- Upload Proof of Payment -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Upload Proof of Payment</label>
                <input type="file" name="payment_proof" class="form-control" accept="image/*" required>
            </div>

            <!-- Optional: Transaction number -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Transaction Number (Optional)</label>
                <input type="text" name="transaction_number" class="form-control" placeholder="Enter transaction number">
            </div>

            <!-- Optional: Message to admin -->
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
    const paymentSelect = document.querySelector("select[name='payment_type']");
    const totalElem = document.querySelector(".total-amount");
    const baseTotal = parseFloat("{{ $baseTotal }}");

    const gcashInfo = document.getElementById("gcashInfo");
    const bpiInfo = document.getElementById("bpiInfo");

    // Update payment display dynamically
    function updatePaymentDisplay() {
        const halfPrice = baseTotal / 2;
        if (paymentSelect.value === "half") {
            totalElem.innerHTML = `
                Amount Paid (50%): ₱${halfPrice.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}
                <br><small class="text-muted">Remaining Balance: ₱${halfPrice.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</small>
            `;
        } else {
            totalElem.innerHTML = `<strong>Total Price (with VAT):</strong> ₱${baseTotal.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}`;
        }
    }

    paymentSelect.addEventListener("change", updatePaymentDisplay);

    // Toggle payment method info
    document.getElementById("paymentMethod").addEventListener("change", function() {
        gcashInfo.style.display = this.value === "gcash" ? "block" : "none";
        bpiInfo.style.display = this.value === "bpi" ? "block" : "none";
    });

    // Prevent double submission
    const bookingForm = document.querySelector("form");
    const submitButton = bookingForm.querySelector("button[type='submit']");
    bookingForm.addEventListener("submit", function() {
        submitButton.disabled = true;
        submitButton.textContent = "Submitting...";
    });
});
</script>
@endsection

@section('Footer')
    @include('Footer')
@endsection
