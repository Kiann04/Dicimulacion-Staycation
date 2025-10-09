@extends('layouts.default')

@section('Header')
    @include('Header')
@endsection

@section('content')
<style>
.booking-card { border-radius:1rem; box-shadow:0 10px 25px rgba(0,0,0,0.1); background:#fff; padding:2rem; }
.total-amount { font-size:1.25rem; font-weight:700; color:#007bff; }
.btn-modern { background:linear-gradient(90deg,#007bff 0%,#00c6ff 100%); border:none; color:#fff; padding:0.75rem; border-radius:0.5rem; font-weight:600; }
.btn-modern:hover { background:linear-gradient(90deg,#0056b3 0%,#00a4cc 100%); }
</style>

<div class="container my-5">
    <div class="booking-card mx-auto" style="max-width:600px;">
        <h3 class="fw-bold mb-4 text-center">Confirm Your Booking</h3>

        @php
            use Carbon\Carbon;
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            $nights = max(1, $end->diffInDays($start));
            $base_price = $staycation->house_price * $nights;
            $extraGuests = max(0, $guest_number - 6);
            $extraFee = $extraGuests > 0 ? $extraGuests * 500 : 0;
            $total_price = $base_price + $extraFee;
            $vat_amount = round($total_price - ($total_price / 1.12), 2);
            $base_price = round($total_price - $vat_amount, 2);
            $amount_paid = request()->old('payment_type') === 'half' ? round($total_price/2,2) : $total_price;
            $remaining_balance = $total_price - $amount_paid;
            $formattedStart = $start->format('M d, Y');
            $formattedEnd = $end->format('M d, Y');
        @endphp

        <div class="booking-info mb-4">
            <p><strong>Staycation:</strong> {{ $staycation->house_name }}</p>
            <p><strong>Guests:</strong> {{ $guest_number }}</p>
            <p><strong>Stay Dates:</strong> {{ $formattedStart }} - {{ $formattedEnd }} ({{ $nights }} night{{ $nights>1?'s':'' }})</p>
            <hr>
            <p><strong>Base Price:</strong> ₱{{ number_format($base_price,2) }}</p>
            @if($extraFee > 0)
            <p><strong>Extra Guests Fee:</strong> ₱{{ number_format($extraFee,2) }} ({{ $extraGuests }} extra guest{{ $extraGuests>1?'s':'' }})</p>
            @endif
            <p><strong>VAT (12%):</strong> ₱{{ number_format($vat_amount,2) }}</p>
            <p class="total-amount"><strong>Total Price:</strong> ₱{{ number_format($total_price,2) }}</p>
            @if(request()->old('payment_type')==='half')
            <p><strong>Amount Paid (50%):</strong> ₱{{ number_format($amount_paid,2) }}</p>
            <p><strong>Remaining Balance:</strong> ₱{{ number_format($remaining_balance,2) }}</p>
            @endif
        </div>

        <form action="{{ route('booking.submit', $staycation->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="guest_number" value="{{ $guest_number }}">
            <input type="hidden" name="startDate" value="{{ $startDate }}">
            <input type="hidden" name="endDate" value="{{ $endDate }}">
            <input type="hidden" name="phone" value="{{ $phone ?? Auth::user()->phone ?? old('phone') }}">
            <input type="hidden" name="base_price" value="{{ $base_price }}">
            <input type="hidden" name="total_price" id="total_price_input" value="{{ $total_price }}">
            <input type="hidden" name="vat_amount" value="{{ $vat_amount }}">
            <input type="hidden" name="amount_paid" id="amount_paid_input" value="{{ $amount_paid }}">
            <input type="hidden" name="remaining_balance" id="remaining_balance_input" value="{{ $remaining_balance }}">

            <!-- Payment Option -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Payment Option</label>
                <select name="payment_type" id="payment_type" class="form-select" required>
                    <option value="">Select option</option>
                    <option value="half" {{ request()->old('payment_type')==='half'?'selected':'' }}>Half Payment (50%)</option>
                    <option value="full" {{ request()->old('payment_type')==='full'?'selected':'' }}>Full Payment</option>
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

            <div id="gcashInfo" class="p-3 bg-light border rounded mb-3" style="display:none;">
                <h6 class="fw-semibold text-primary">GCash Information</h6>
                <p><strong>Account Name:</strong> Dicimulacion Staycation</p>
                <p><strong>GCash Number:</strong> 0917-123-4567</p>
            </div>
            <div id="bpiInfo" class="p-3 bg-light border rounded mb-3" style="display:none;">
                <h6 class="fw-semibold text-primary">BPI Bank Information</h6>
                <p><strong>Account Name:</strong> Dicimulacion Staycation</p>
                <p><strong>Account Number:</strong> 1234-5678-90</p>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Upload Proof of Payment</label>
                <input type="file" name="payment_proof" class="form-control" accept="image/*" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Transaction Number (Optional)</label>
                <input type="text" name="transaction_number" class="form-control" placeholder="Enter transaction number">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Message to Admin (Optional)</label>
                <textarea name="message" class="form-control" rows="3" placeholder="Any special requests or notes"></textarea>
            </div>

            <button type="submit" class="btn-modern w-100 mt-3">Submit Booking Request</button>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function(){
    const paymentSelect = document.getElementById("payment_type");
    const totalElem = document.querySelector(".total-amount");
    const totalPriceInput = document.getElementById("total_price_input");
    const amountPaidInput = document.getElementById("amount_paid_input");
    const remainingBalanceInput = document.getElementById("remaining_balance_input");
    const totalPrice = parseFloat(totalPriceInput.value);

    const gcashInfo = document.getElementById("gcashInfo");
    const bpiInfo = document.getElementById("bpiInfo");

    function updatePaymentDisplay(){
        if(paymentSelect.value==='half'){
            const half = totalPrice/2;
            totalElem.innerHTML = `Amount Paid (50%): ₱${half.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}
            <br><small class="text-muted">Remaining Balance: ₱${half.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</small>`;
            amountPaidInput.value = half;
            remainingBalanceInput.value = half;
        } else {
            totalElem.innerHTML = `<strong>Total Price:</strong> ₱${totalPrice.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}`;
            amountPaidInput.value = totalPrice;
            remainingBalanceInput.value = 0;
        }
    }

    paymentSelect.addEventListener("change", updatePaymentDisplay);

    document.getElementById("paymentMethod").addEventListener("change", function(){
        gcashInfo.style.display = this.value==='gcash'?'block':'none';
        bpiInfo.style.display = this.value==='bpi'?'block':'none';
    });
});
</script>
@endsection

@section('Footer')
    @include('Footer')
@endsection
