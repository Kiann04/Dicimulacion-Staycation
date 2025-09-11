@extends('layouts.default')

@section('Header')
    @include('Header')
@endsection

<body>
  <section class="terms-container">
    <h2>Terms and Conditions</h2>
    <p class="intro">By booking with Dicimulacion Staycation, you agree to the following terms and conditions:</p>

    <div class="terms-content">
      <h3>1. Booking & Payment</h3>
      <p>All bookings must be confirmed with accurate guest details. Payments must be settled before the check-in date unless otherwise agreed.</p>
      <p>If your booking is <strong>approved</strong>, you are required to pay within <strong>24 hours</strong>. Failure to do so will result in your reservation being <strong>automatically cancelled</strong>.</p>

      <h3>2. Cancellation</h3>
      <p>Cancellations made at least 48 hours before the check-in date will be eligible for a refund. Late cancellations may be subject to fees.</p>
      <p>Guests can <strong>only cancel their reservation if the booking status is still Pending</strong>. Once approved, cancellations are no longer possible by the guest.</p>

      <h3>3. Guest Responsibilities</h3>
      <p>Guests are expected to respect the property, neighbors, and house rules. Any damages caused during the stay may result in additional charges.</p>

      <h3>4. Liability</h3>
      <p>The management is not responsible for lost or stolen belongings. Guests are encouraged to secure their valuables.</p>

      <h3>5. Amendments</h3>
      <p>Dicimulacion Staycation reserves the right to update these terms and conditions at any time.</p>
    </div>
  </section>
</body>

@section('Footer')
    @include('Footer')
@endsection
