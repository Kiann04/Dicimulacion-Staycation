@extends('layouts.default')

@section('Header')
    @include('Header')
@endsection

@section('content')
<div class="container my-5">
    <h3 class="fw-bold mb-4 text-center">Confirm Your Booking</h3>

    <div class="card shadow-sm border-0 rounded-4 mx-auto" style="max-width: 600px;">
        <div class="card-body p-4">
            <p><strong>Staycation:</strong> {{ $staycation->house_name }}</p>
            <p><strong>Total Amount:</strong> ₱{{ number_format($totalWithVat, 2) }}</p>

            <form action="{{ route('booking.submit', $staycation->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="guest_number" value="{{ $guest_number }}">
                <input type="hidden" name="startDate" value="{{ $startDate }}">
                <input type="hidden" name="endDate" value="{{ $endDate }}">

                {{-- ✅ Payment Option --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Payment Option</label>
                    <select name="payment_type" class="form-select" required>
                        <option value="">Select option</option>
                        <option value="half">Half Payment (50%)</option>
                        <option value="full">Full Payment</option>
                    </select>
                </div>

                {{-- ✅ Payment Method --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Payment Method</label>
                    <select name="payment_method" id="paymentMethod" class="form-select" required>
                        <option value="">Select method</option>
                        <option value="gcash">GCash</option>
                        <option value="bpi">BPI Bank Transfer</option>
                    </select>
                </div>

                {{-- ✅ GCash Info --}}
                <div id="gcashInfo" class="bank-info p-3 bg-light border rounded mb-3" style="display:none;">
                    <h6 class="fw-semibold text-primary">GCash Information</h6>
                    <p class="mb-1"><strong>Account Name:</strong> Dicimulacion Staycation</p>
                    <p class="mb-1"><strong>GCash Number:</strong> 0917-123-4567</p>
                    <p class="mb-0 text-muted">Please upload your GCash payment screenshot below.</p>
                </div>

                {{-- ✅ BPI Info --}}
                <div id="bpiInfo" class="bank-info p-3 bg-light border rounded mb-3" style="display:none;">
                    <h6 class="fw-semibold text-primary">BPI Bank Information</h6>
                    <p class="mb-1"><strong>Account Name:</strong> Dicimulacion Staycation</p>
                    <p class="mb-1"><strong>Account Number:</strong> 1234-5678-90</p>
                    <p class="mb-0 text-muted">Please upload your deposit slip or transfer screenshot below.</p>
                </div>

                {{-- ✅ Proof of Payment --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Upload Proof of Payment</label>
                    <input type="file" name="payment_proof" class="form-control" accept="image/*" required>
                </div>

                {{-- ✅ Transaction Number --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Transaction Number <span class="text-muted">(Optional)</span></label>
                    <input type="text" name="transaction_number" class="form-control" placeholder="Enter transaction number">
                </div>

                {{-- ✅ Message to Admin --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Message to Admin <span class="text-muted">(Optional)</span></label>
                    <textarea name="message" class="form-control" rows="3" placeholder="Any special notes..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-100">Submit Booking Request</button>
            </form>
        </div>
    </div>
</div>

{{-- ✅ Script to toggle payment info --}}
<script>
document.addEventListener("DOMContentLoaded", function () {
    const paymentSelect = document.getElementById("paymentMethod");
    const gcashInfo = document.getElementById("gcashInfo");
    const bpiInfo = document.getElementById("bpiInfo");

    paymentSelect.addEventListener("change", function () {
        gcashInfo.style.display = "none";
        bpiInfo.style.display = "none";

        if (this.value === "gcash") {
            gcashInfo.style.display = "block";
        } else if (this.value === "bpi") {
            bpiInfo.style.display = "block";
        }
    });
});
</script>
@endsection
