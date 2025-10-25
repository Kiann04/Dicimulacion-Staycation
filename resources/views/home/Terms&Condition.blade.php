@extends('layouts.default')

@section('Header')
    @include('Header')
@endsection

<body>
  <div class="container my-5">
    <section class="terms-container">
      <h2 class="mb-4 text-center fw-bold">Terms and Conditions</h2>
      <p class="intro text-center mb-5 text-muted">
        By booking with Dicimulacion Staycation, you agree to the following terms and conditions:
      </p>

      <div class="row g-4">
        <!-- Booking & Payment -->
        <div class="col-12 col-md-6">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h5 class="card-title fw-semibold">1. Booking & Payment</h5>
              <p class="card-text">
                Bookings are only confirmed once payment has been made and verified by Dicimulacion Staycation. 
                Guests are required to complete their payment immediately after submitting the booking form.
              </p>
              <p class="card-text">
                <strong>Note:</strong> Unpaid bookings will not be considered valid and will not reserve your chosen date. 
                Please upload your proof of payment through the booking page or send it directly to the admin for verification.
              </p>
              <p class="card-text">
                <strong>Important:</strong> The amount shown on your online receipt reflects only payments made through the 
                online booking system. Any additional payments (e.g., bank transfer or on-site payment) are not reflected online. 
                Please keep your official receipt as proof of full payment.
              </p>
            </div>
          </div>
        </div>

        <!-- Cancellation -->
        <div class="col-12 col-md-6">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h5 class="card-title fw-semibold">2. Cancellation</h5>
              <p class="card-text mb-2">
                Guests may request rescheduling at least <strong>14 days</strong> before the check-in date. 
                Cancellations are not allowed once payment has been verified.
              </p>
              <p class="card-text mb-2">
                <strong>No Refund Policy:</strong><br>
                All confirmed bookings and verified payments are non-refundable. Refunds will not be issued for cancellations, no-shows, or shortened stays.
              </p>
              <p class="card-text mb-0">
                <strong>Note:</strong> In case of emergencies or unavoidable events, please contact the admin directly 
                for possible rescheduling assistance.
              </p>
            </div>
          </div>
        </div>


        <!-- Guest Responsibilities -->
        <div class="col-12 col-md-6">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h5 class="card-title fw-semibold">3. Guest Responsibilities</h5>
              <p class="card-text">
                Guests are expected to respect the property, neighbors, and house rules. 
                Any damages caused during the stay may result in additional charges.
              </p>
              <p class="card-text">
                <strong>Additional Guests:</strong> The maximum number of guests allowed per booking is 
                <strong>eight (8)</strong>. The standard occupancy is six (6) guests. 
                An additional charge of <strong>₱500 per extra person</strong> applies for the 7th and 8th guests. 
                Bookings exceeding eight (8) guests are not permitted for safety and comfort reasons.
              </p>
            </div>
          </div>
        </div>


        <!-- Liability -->
        <div class="col-12 col-md-6">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h5 class="card-title fw-semibold">4. Liability</h5>
              <p class="card-text">
                Dicimulacion Staycation is not responsible for lost or stolen belongings. 
                Guests are advised to secure their valuables at all times.
              </p>
            </div>
          </div>
        </div>

        <!-- Amendments -->
        <div class="col-12">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h5 class="card-title fw-semibold">5. Amendments</h5>
              <p class="card-text">
                Dicimulacion Staycation reserves the right to update these terms and conditions 
                without prior notice. Please review them before confirming your booking.
              </p>
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
