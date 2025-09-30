@extends('layouts.default')

@section('Header')
    @include('Header')
@endsection

<body>
  <div class="container my-5">
    <section class="terms-container">
      <h2 class="mb-4 text-center fw-bold">Terms and Conditions</h2>
      <p class="intro text-center mb-5 text-muted">By booking with Dicimulacion Staycation, you agree to the following terms and conditions:</p>

      <div class="row g-4">
        <!-- Booking & Payment -->
        <div class="col-12 col-md-6">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h5 class="card-title fw-semibold">1. Booking & Payment</h5>
              <p class="card-text">All bookings must be confirmed with accurate guest details. Payments must be settled before the check-in date unless otherwise agreed.</p>
              <p class="card-text"><strong>Note:</strong> If your booking is <strong>approved</strong>, you are required to pay within <strong>24 hours</strong>. Failure to do so will result in your reservation being <strong>automatically cancelled</strong>.</p>
            </div>
          </div>
        </div>

        <!-- Cancellation -->
        <div class="col-12 col-md-6">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h5 class="card-title fw-semibold">2. Cancellation</h5>
              <p class="card-text">Cancellations made at least 48 hours before the check-in date will be eligible for a refund. Late cancellations may be subject to fees.</p>
              <p class="card-text"><strong>Note:</strong> Guests can only cancel their reservation if the booking status is still Pending. Once approved, cancellations are no longer possible by the guest.</p>
            </div>
          </div>
        </div>

        <!-- Guest Responsibilities -->
        <div class="col-12 col-md-6">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h5 class="card-title fw-semibold">3. Guest Responsibilities</h5>
              <p class="card-text">Guests are expected to respect the property, neighbors, and house rules. Any damages caused during the stay may result in additional charges.</p>
            </div>
          </div>
        </div>

        <!-- Liability -->
        <div class="col-12 col-md-6">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h5 class="card-title fw-semibold">4. Liability</h5>
              <p class="card-text">The management is not responsible for lost or stolen belongings. Guests are encouraged to secure their valuables.</p>
            </div>
          </div>
        </div>

        <!-- Amendments -->
        <div class="col-12">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h5 class="card-title fw-semibold">5. Amendments</h5>
              <p class="card-text">Dicimulacion Staycation reserves the right to update these terms and conditions at any time.</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</body>

@section('Footer')
    @include('Footer')
@endsection
