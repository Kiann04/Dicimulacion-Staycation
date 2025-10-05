@extends('layouts.default')

@section('content')
<section class="container my-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card shadow-lg border-0 rounded-4">
        <div class="card-body p-4">
          <h3 class="fw-bold text-center mb-4">Review Your Booking</h3>

          <div class="mb-4">
            <h5>{{ $staycation->house_name }}</h5>
            <p>
              <strong>From:</strong> {{ $startDate }} <br>
              <strong>To:</strong> {{ $endDate }} <br>
              <strong>Guests:</strong> {{ $guest_number }} <br>
              <strong>Total Price:</strong> ₱{{ number_format($totalPrice, 2) }}
            </p>
          </div>

          <form action="{{ route('booking.submit', $staycation->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="startDate" value="{{ $startDate }}">
            <input type="hidden" name="endDate" value="{{ $endDate }}">
            <input type="hidden" name="guest_number" value="{{ $guest_number }}">
            <input type="hidden" name="totalPrice" value="{{ $totalPrice }}">
            <input type="hidden" name="name" value="{{ $name }}">
            <input type="hidden" name="phone" value="{{ $phone }}">

            <div class="mb-3">
              <label class="form-label fw-bold">Payment Option</label>
              <select name="payment_option" class="form-select" required>
                <option value="">Select</option>
                <option value="half">Half Payment (50%)</option>
                <option value="full">Full Payment</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Proof of Payment</label>
              <input type="file" name="payment_proof" class="form-control" accept="image/*" required>
              <small class="text-muted">Upload a screenshot or photo of your payment.</small>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Message to Admin (Optional)</label>
              <textarea name="message" class="form-control" rows="3" placeholder="Enter a note or request"></textarea>
            </div>

            <button type="submit" class="btn btn-success w-100 py-2">Submit Booking Request</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
