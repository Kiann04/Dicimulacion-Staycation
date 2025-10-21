@extends('layouts.default')
@section('Header')
    @include('Header')
@endsection
<section class="container my-5 pt-5">
  <div class="row g-4 align-items-stretch">
    
    <!-- ✅ Left: Booking Form -->
    <div class="col-lg-6 d-flex">
      <div class="card shadow-sm border-0 flex-fill d-flex flex-column">
        <div class="card-body p-4 flex-grow-1">
          <h3 class="fw-bold">Booking Form for {{ $staycation->house_name }}</h3>
          <p class="text-muted">Enter the required information to book</p>

          @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
          @endif
          @if(session('message'))
            <div class="alert alert-danger">{!! nl2br(e(session('message'))) !!}</div>
          @endif

          <form action="{{ route('booking.preview', $staycation->id) }}" method="POST" id="bookingForm">
            @csrf

            <!-- Full Name -->
            <div class="mb-3">
              <label for="name" class="form-label">Full Name</label>
              <input type="text" id="name" name="name" class="form-control" placeholder="Name" required
                value="{{ old('name', Auth::user()->name ?? '') }}">
            </div>

            <!-- Contact Number -->
            <div class="mb-3">
              <label for="phone" class="form-label">Contact Number</label>
              <div class="input-group">
                <span class="input-group-text">+63</span>
                <input type="tel" id="phone" name="phone" class="form-control"
                  placeholder="9123456789" required pattern="[0-9]{10}"
                  title="Enter a valid 10-digit Philippine phone number"
                  value="{{ old('phone', Auth::user()?->phone ? ltrim(Auth::user()?->phone, '+63') : '') }}">
              </div>
            </div>

            <!-- Guests -->
            <div class="mb-3">
              <label for="guest_number" class="form-label">Guests</label>
              <input type="number" id="guest_number" name="guest_number" class="form-control"
                placeholder="Guest/s" required min="1" value="{{ old('guest_number') }}">
            </div>

            <!-- Dates -->
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label for="startDate" class="form-label">Date of Arrival</label>
                <input type="date" id="startDate" name="startDate" class="form-control" required
                  min="{{ now()->toDateString() }}" max="2026-12-31" value="{{ old('startDate') }}">
              </div>
              <div class="col-md-6">
                <label for="endDate" class="form-label">Date of Departure</label>
                <input type="date" id="endDate" name="endDate" class="form-control" required
                  min="{{ now()->toDateString() }}" max="2026-12-31" value="{{ old('endDate') }}">
              </div>
            </div>

            <!-- Price Summary -->
            <div id="price-summary" class="border-top pt-3 mb-3" style="display:none;">
              <p>₱<span id="price-per-night">{{ $staycation->house_price }}</span> / night</p>
              <p id="total-price" class="fw-bold text-success"></p>
            </div>

            <!-- Terms Modal Trigger -->
            <div class="mb-3 text-center">
              <button type="button" class="btn btn-outline-secondary w-100" data-bs-toggle="modal" data-bs-target="#termsModal">
                View Terms & Conditions
              </button>
            </div>
            
            <!-- 🏠 Change Staycation Button -->
            <div class="mb-3 text-center">
              <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#changeStaycationModal">
                Change Staycation
              </button>
              <small class="text-muted d-block mt-1">
                If your selected date is unavailable, choose another house.
              </small>
            </div>
            <!-- Hidden Agreement Checkbox -->
            <input type="hidden" name="terms_privacy" id="terms_privacy" value="0">

            <!-- Submit Button -->
            @auth
              <button type="submit" id="submitBtn" class="btn btn-primary w-100 fw-semibold" disabled>
                Preview Booking
              </button>
            @else
              <a href="{{ route('login') }}" class="btn btn-secondary w-100 disabled">
                Please log in to reserve
              </a>
            @endauth
          </form>
        </div>
      </div>
    </div>

    <!-- ✅ Right: Calendar -->
    <div class="col-lg-6 d-flex">
      <div class="card shadow-sm border-0 flex-fill d-flex flex-column">
        <div class="card-body p-4 flex-grow-1">
          <h4 class="fw-bold mb-3">Availability Calendar</h4>
          <div id="calendar"></div>
        </div>
      </div>
    </div>

  </div>

  <!-- ✅ Image Carousel below both columns -->
  <div class="row mt-4">
    <div class="col-12">
      <div id="staycationCarousel" class="carousel slide carousel-fade shadow-sm rounded overflow-hidden" data-bs-ride="carousel" data-bs-interval="3000">
        <div class="carousel-inner">
          @php
            $images = collect([$staycation->house_image]);
            if (isset($staycation->images) && $staycation->images->isNotEmpty()) {
                $images = $images->merge($staycation->images->pluck('image_path'));
            }
          @endphp

          @foreach($images as $i => $img)
            <div class="carousel-item {{ $i === 0 ? 'active' : '' }}">
              <img 
                src="{{ asset('storage/' . $img) }}" 
                class="d-block w-100 rounded-4 shadow-sm"
                alt="Staycation Image"
                style="height: 550px; object-fit: contain; background-color: #f8f9fa;">
            </div>
          @endforeach
        </div>

        @if($images->count() > 1)
          <button class="carousel-control-prev" type="button" data-bs-target="#staycationCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon bg-dark rounded-circle p-2"></span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#staycationCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon bg-dark rounded-circle p-2"></span>
          </button>
        @endif
      </div>

      <div class="text-center mt-3">
        <button type="button" class="btn btn-outline-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#photoModal">
          <i class="bi bi-images me-2"></i> Show All Photos
        </button>
      </div>
    </div>
  </div>
  <!-- Terms & Conditions Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="termsModalLabel">Terms & Conditions</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <p>
          By proceeding with your booking, you agree to the following:
          <br><br>
          • The reservation will only be confirmed once payment is verified.<br>
          • Cancellations within 48 hours before check-in are non-refundable.<br>
          • Guests are responsible for any damages during their stay.<br>
          • Please review our full 
          <a href="{{ url('/terms') }}" target="_blank">Terms and Conditions</a> and 
          <a href="{{ url('/privacy') }}" target="_blank">Privacy Policy</a>.
        </p>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="agreeBtn">I Agree</button>
      </div>
    </div>
  </div>
</div>

  <script>
document.addEventListener('DOMContentLoaded', function () {
  const agreeBtn = document.getElementById('agreeBtn');
  const submitBtn = document.getElementById('submitBtn');
  const termsPrivacy = document.getElementById('terms_privacy');

  agreeBtn.addEventListener('click', function () {
    // Mark terms as agreed
    termsPrivacy.value = 1;
    // Enable submit
    submitBtn.disabled = false;
    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('termsModal'));
    modal.hide();
  });
});
</script>

</section>

<!-- ✅ Modal: Show All Photos -->
<div class="modal fade" id="photoModal" tabindex="-1" aria-labelledby="photoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 overflow-hidden">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold" id="photoModalLabel">{{ $staycation->house_name }} - All Photos</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body bg-light">
        <div class="row g-3">
          @foreach($images as $img)
            <div class="col-md-4 col-lg-3">
              <img src="{{ asset('storage/' . $img) }}" class="img-fluid rounded shadow-sm" alt="Gallery Photo">
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</div>

<!-- 🏠 Change Staycation Modal -->
<div class="modal fade" id="changeStaycationModal" tabindex="-1" aria-labelledby="changeStaycationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-semibold" id="changeStaycationModalLabel">Select Another Staycation</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="row g-4">
          @foreach($otherStaycations as $house)
            <div class="col-md-6">
              <div class="card h-100 shadow-sm border-0">
                <img 
                  src="{{ asset('storage/' . $house->house_image) }}" 
                  class="card-img-top rounded-top"
                  alt="{{ $house->house_name }}"
                  style="height: 200px; object-fit: cover;"
                >
                <div class="card-body">
                  <h5 class="card-title fw-bold">{{ $house->house_name }}</h5>
                  <p class="text-muted mb-2">₱{{ number_format($house->house_price, 2) }} / night</p>
                  <p class="small text-secondary mb-3" style="min-height: 40px;">
                    {{ Str::limit($house->house_description, 80) }}
                  </p>
                  <a href="{{ route('booking.form', $house->id) }}" class="btn btn-primary w-100">
                    Select This Staycation
                  </a>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@if(session('availableStaycations'))
  @php
    $availableStaycations = session('availableStaycations');
    $name = session('name');
    $phone = session('phone');
    $guest_number = session('guest_number');
    $startDate = session('startDate');
    $endDate = session('endDate');
  @endphp

  <!-- Modal -->
  <div class="modal fade" id="availableStaycationsModal" tabindex="-1" aria-labelledby="availableStaycationsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header bg-warning text-dark">
          <h5 class="modal-title fw-bold" id="availableStaycationsModalLabel">
            The selected staycation is unavailable
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
          <p class="text-muted">Please choose from these available staycations for your selected dates:</p>

          <div class="row g-4">
            @forelse($availableStaycations as $alt)
              <div class="col-md-4 col-sm-6">
                <div class="card shadow-sm border-0 h-100">
                  <div class="position-relative">
                    <img src="{{ asset($alt->house_image ?? 'Assets/default.jpg') }}" 
                         class="card-img-top" 
                         style="height:200px; object-fit:cover; border-top-left-radius: .5rem; border-top-right-radius: .5rem;">
                    <span class="badge bg-success position-absolute top-0 end-0 m-2">Available</span>
                  </div>

                  <div class="card-body d-flex flex-column">
                    <h5 class="card-title fw-bold">{{ $alt->house_name }}</h5>
                    <p class="card-text text-muted mb-1">₱{{ number_format($alt->house_price, 2) }} / night</p>
                    <p class="card-text small text-secondary flex-grow-1">
                      {{ $alt->house_location ?? 'No location info' }}
                    </p>

                    <form action="{{ route('booking.preview', $alt->id) }}" method="POST">
                      @csrf
                      <input type="hidden" name="name" value="{{ $name }}">
                      <input type="hidden" name="phone" value="{{ $phone }}">
                      <input type="hidden" name="guest_number" value="{{ $guest_number }}">
                      <input type="hidden" name="startDate" value="{{ $startDate }}">
                      <input type="hidden" name="endDate" value="{{ $endDate }}">
                      <button type="submit" class="btn btn-primary w-100 fw-semibold mt-2">
                        Book this Staycation
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            @empty
              <p class="text-danger">No other staycations available for the selected dates.</p>
            @endforelse
          </div>
        </div>

        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Auto-show modal when there are available staycations -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      var modal = new bootstrap.Modal(document.getElementById('availableStaycationsModal'));
      modal.show();
    });
  </script>
@endif


{{-- Modern Services Section --}}
<section class="services-section py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill mb-3">Amenities & Services</span>
            <h2 class="fw-bold mb-4">What this place offers</h2>
            <p class="text-muted fs-5">{{ $staycation->house_name }} provides the following amenities for a comfortable stay.</p>
        </div>

        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-4">
            <!-- Scenic Views -->
            <div class="col text-center">
                <div class="amenity-card p-3 rounded shadow-sm transition-hover">
                    <i class="fas fa-mountain fa-2x text-primary mb-2"></i>
                    <div class="fw-bold">Scenic Views</div>
                    <div class="text-muted small">City skyline, lake, and mountains</div>
                </div>
            </div>
            <!-- Kitchen -->
            <div class="col text-center">
                <div class="amenity-card p-3 rounded shadow-sm transition-hover">
                    <i class="fas fa-utensils fa-2x text-primary mb-2"></i>
                    <div class="fw-bold">Kitchen</div>
                    <div class="text-muted small">Dining table, stove, refrigerator</div>
                </div>
            </div>
            <!-- Essentials -->
            <div class="col text-center">
                <div class="amenity-card p-3 rounded shadow-sm transition-hover">
                    <i class="fas fa-soap fa-2x text-primary mb-2"></i>
                    <div class="fw-bold">Essentials</div>
                    <div class="text-muted small">Towels, bed sheets, toiletries</div>
                </div>
            </div>
            <!-- In-room Entertainment -->
            <div class="col text-center">
                <div class="amenity-card p-3 rounded shadow-sm transition-hover">
                    <i class="fas fa-tv fa-2x text-primary mb-2"></i>
                    <div class="fw-bold">In-room Entertainment</div>
                    <div class="text-muted small">TV, books, board games</div>
                </div>
            </div>
            <!-- Outdoor Amenities -->
            <div class="col text-center">
                <div class="amenity-card p-3 rounded shadow-sm transition-hover">
                    <i class="fas fa-tree fa-2x text-primary mb-2"></i>
                    <div class="fw-bold">Outdoor Amenities</div>
                    <div class="text-muted small">Patio, BBQ grill, dining area</div>
                </div>
            </div>
            <!-- Pool Access -->
            <div class="col text-center">
                <div class="amenity-card p-3 rounded shadow-sm transition-hover">
                    <i class="fas fa-swimming-pool fa-2x text-primary mb-2"></i>
                    <div class="fw-bold">Pool Access</div>
                    <div class="text-muted small">Refresh anytime</div>
                </div>
            </div>
            <!-- Free Parking -->
            <div class="col text-center">
                <div class="amenity-card p-3 rounded shadow-sm transition-hover">
                    <i class="fas fa-parking fa-2x text-primary mb-2"></i>
                    <div class="fw-bold">Free Parking</div>
                    <div class="text-muted small">On-site & street</div>
                </div>
            </div>
            <!-- Pet-friendly -->
            <div class="col text-center">
                <div class="amenity-card p-3 rounded shadow-sm transition-hover">
                    <i class="fas fa-paw fa-2x text-primary mb-2"></i>
                    <div class="fw-bold">Pet-friendly</div>
                    <div class="text-muted small">Bring your furry friends</div>
                </div>
            </div>
            <!-- Breakfast Provided -->
            <div class="col text-center">
                <div class="amenity-card p-3 rounded shadow-sm transition-hover">
                    <i class="fas fa-coffee fa-2x text-primary mb-2"></i>
                    <div class="fw-bold">Free Breakfast</div>
                    <div class="text-muted small">Complimentary morning meal</div>
                </div>
            </div>
            <!-- Long-term Stay -->
            <div class="col text-center">
                <div class="amenity-card p-3 rounded shadow-sm transition-hover">
                    <i class="fas fa-calendar-check fa-2x text-primary mb-2"></i>
                    <div class="fw-bold">Long-term Stay</div>
                    <div class="text-muted small">(28+ days)</div>
                </div>
            </div>
            <!-- Home Safety -->
            <div class="col text-center">
                <div class="amenity-card p-3 rounded shadow-sm transition-hover">
                    <i class="fas fa-shield-alt fa-2x text-primary mb-2"></i>
                    <div class="fw-bold">Home Safety</div>
                    <div class="text-muted small">Fire extinguisher</div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.card {
    min-height: 500px; /* Adjust as needed */
}

.amenity-card {
    background: #fff;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.amenity-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
}
.transition-hover i {
    transition: transform 0.3s ease;
}
.transition-hover:hover i {
    transform: scale(1.2);
}
</style>



<style>
/* Modern Hover Effects for Service Cards (same as before) */
.service-card {
    cursor: pointer;
}

.service-card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1) !important;
    background: rgba(255, 255, 255, 1) !important;
}

.service-card:hover .icon i {
    transform: scale(1.1);
    transition: transform 0.3s ease;
}

.service-card .icon i {
    transition: transform 0.3s ease;
}

/* Ensure backdrop-filter support (fallback for older browsers) */
@supports not (backdrop-filter: blur(10px)) {
    .service-card {
        background: rgba(255, 255, 255, 0.95);
    }
}

/* Subtle animation on load */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.service-card {
    animation: fadeInUp 0.6s ease forwards;
}

.service-card:nth-child(1) { animation-delay: 0.1s; }
.service-card:nth-child(2) { animation-delay: 0.2s; }
.service-card:nth-child(3) { animation-delay: 0.3s; }
.service-card:nth-child(4) { animation-delay: 0.4s; }
.service-card:nth-child(5) { animation-delay: 0.5s; }
.service-card:nth-child(6) { animation-delay: 0.6s; }
.service-card:nth-child(7) { animation-delay: 0.7s; }
.service-card:nth-child(8) { animation-delay: 0.8s; }
.service-card:nth-child(9) { animation-delay: 0.9s; }
.service-card:nth-child(10) { animation-delay: 1.0s; }
.service-card:nth-child(11) { animation-delay: 1.1s; }
.service-card:nth-child(12) { animation-delay: 1.2s; }
</style>
<!-- Reviews Section -->
<section class="container my-5" id="reviews">
    <h2 class="fw-bold mb-5 text-center display-6">What Our Guests Say</h2>

    <!-- Rating Summary -->
    @if($totalReviews > 0)
    <div class="text-center mb-5">
        <h3 class="fw-bold mb-2">
            {{ $averageRating }}
            <span class="ms-1">
                @php
                    $fullStars = floor($averageRating);
                    $halfStar = ($averageRating - $fullStars) >= 0.5 ? 1 : 0;
                    $emptyStars = 5 - ($fullStars + $halfStar);
                @endphp

                {{-- Full stars --}}
                @for ($i = 0; $i < $fullStars; $i++)
                    <i class='bx bxs-star text-warning fs-4'></i>
                @endfor

                {{-- Half star --}}
                @if ($halfStar)
                    <i class='bx bxs-star-half text-warning fs-4'></i>
                @endif

                {{-- Empty stars --}}
                @for ($i = 0; $i < $emptyStars; $i++)
                    <i class='bx bx-star text-warning fs-4'></i>
                @endfor
            </span>
        </h3>

        <p class="text-muted mb-4">Average Rating from {{ $totalReviews }} Review{{ $totalReviews == 1 ? '' : 's' }}</p>

        <div class="mx-auto" style="max-width: 300px;">
            @foreach ([5,4,3,2,1] as $star)
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <div class="d-flex align-items-center">
                        <span class="me-2">{{ $star }}</span>
                        <i class='bx bxs-star text-warning'></i>
                    </div>
                    <div class="progress flex-grow-1 mx-2" style="height: 8px;">
                        <div class="progress-bar bg-warning" role="progressbar" 
                            style="width: {{ $totalReviews > 0 ? ($starCounts[$star] / $totalReviews) * 100 : 0 }}%;">
                        </div>
                    </div>
                    <span>{{ $starCounts[$star] }}</span>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Individual Reviews -->
    <div class="row g-4 justify-content-center">
        @forelse($reviews as $review)
        <div class="col-sm-12 col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm rounded-4 p-4 position-relative hover-shadow transition">
                
                <!-- Profile Picture -->
                <div class="d-flex justify-content-center mb-3">
                    <img 
                        src="{{ $review->user && $review->user->photo ? asset($review->user->photo) : asset('Assets/default.png') }}" 
                        class="rounded-circle shadow-sm" 
                        style="width:80px;height:80px;object-fit:cover;" 
                        alt="{{ $review->user ? $review->user->name : 'Guest' }}">
                </div>

                <!-- Guest Name -->
                <h5 class="fw-bold text-center mb-2">{{ $review->user ? $review->user->name : 'Guest' }}</h5>

                <!-- Star Rating -->
                <div class="d-flex justify-content-center mb-3">
                    @for ($i = 0; $i < $review->rating; $i++)
                        <i class='bx bxs-star text-warning me-1'></i>
                    @endfor
                </div>

                <!-- Review Text -->
                <p class="text-center text-muted small mb-0 fst-italic">"{{ $review->comment }}"</p>

                <!-- Optional: subtle background icon -->
                <div class="position-absolute top-0 end-0 opacity-10 fs-1 me-3 mt-3">
                    <i class='bx bxs-quote-right'></i>
                </div>
            </div>
        </div>
        @empty
        <p class="text-center text-muted">No reviews yet for this staycation.</p>
        @endforelse
    </div>
</section>

@section('content')
@include('partials.chatbot')
@endsection

<!-- FullCalendar + Price Calculation -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const startInput = document.getElementById("startDate");
    const endInput = document.getElementById("endDate");
    const priceSummary = document.getElementById("price-summary");
    const totalPriceElem = document.getElementById("total-price");
    const pricePerNight = parseFloat(document.getElementById("price-per-night").innerText);
    const form = document.querySelector("form");
    const guestInput = document.querySelector('input[name="guest_number"]');

    // ✅ Philippine Time Display
    const timeDisplay = document.createElement("div");
    timeDisplay.style = "margin-bottom: 15px; font-weight: 600; color: #1e293b;";
    timeDisplay.id = "ph-time-display";
    document.body.prepend(timeDisplay);

    function updatePHTime() {
        const nowPH = new Date().toLocaleString("en-US", { timeZone: "Asia/Manila" });
        timeDisplay.innerHTML = "🇵🇭 Current Philippine Time: " + nowPH;
    }
    updatePHTime();
    setInterval(updatePHTime, 1000);

    // ✅ Force timezone to Asia/Manila
    const nowInPH = new Date().toLocaleString("en-US", { timeZone: "Asia/Manila" });
    const today = new Date(nowInPH).toISOString().split("T")[0];

    // Set minimum selectable dates
    if (startInput) startInput.min = today;
    if (endInput) endInput.min = today;

    // 🧮 Price Calculation
    function calculatePrice() {
        if (startInput.value && endInput.value) {
            const start = new Date(startInput.value);
            const end = new Date(endInput.value);

            if (end > start) {
                const nights = Math.floor((end - start) / (1000 * 60 * 60 * 24));
                let total = nights * pricePerNight;

                const guests = parseInt(guestInput.value) || 0;
                if (guests > 6) {
                    const extraGuests = guests - 6;
                    const extraFee = extraGuests * 500;
                    total += extraFee;
                    totalPriceElem.textContent =
                        `Total for ${nights} night${nights > 1 ? 's' : ''} (₱${extraFee.toLocaleString()} extra for ${extraGuests} guest${extraGuests > 1 ? 's' : ''}): ₱${total.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
                } else {
                    totalPriceElem.textContent =
                        `Total for ${nights} night${nights > 1 ? 's' : ''}: ₱${total.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
                }

                priceSummary.style.display = "block";
            } else {
                priceSummary.style.display = "none";
            }
        } else {
            priceSummary.style.display = "none";
        }
    }

    // Date change listeners
    startInput.addEventListener("change", function () {
        const arrival = new Date(this.value);
        const minDeparture = new Date(arrival);
        minDeparture.setDate(arrival.getDate() + 1);
        endInput.min = minDeparture.toISOString().split("T")[0];

        if (endInput.value && new Date(endInput.value) < minDeparture) {
            endInput.value = endInput.min;
        }
        calculatePrice();
    });

    endInput.addEventListener("change", calculatePrice);
    guestInput.addEventListener("input", calculatePrice);

    // Validate before submit
    if (form) {
        form.addEventListener("submit", function (e) {
            const start = new Date(startInput.value);
            const end = new Date(endInput.value);
            const today = new Date();
            const endLimit = new Date("2026-12-31");
            today.setHours(0, 0, 0, 0);

            if (end <= start) {
                e.preventDefault();
                alert("Departure date must be at least one day after arrival date.");
                return;
            }

            if (start < today || end > endLimit) {
                e.preventDefault();
                alert("Bookings are only allowed from today up to December 31, 2026.");
                return;
            }
        });
    }

    // 🗓️ FullCalendar with PH timezone
    const staycationId = "{{ $staycation->id }}";
    if (document.getElementById("calendar")) {
        fetch(`/events/${staycationId}`)
            .then(res => res.json())
            .then(events => {
                // Filter out past bookings 🔥
                const todayPH = new Date().toLocaleString("en-US", { timeZone: "Asia/Manila" });
                const todayDate = new Date(todayPH);
                todayDate.setHours(0, 0, 0, 0);

                const bookedEvents = events
                    .map(event => {
                        const startPH = new Date(new Date(event.start).toLocaleString("en-US", { timeZone: "Asia/Manila" }));
                        const endPH = new Date(new Date(event.end).toLocaleString("en-US", { timeZone: "Asia/Manila" }));
                        endPH.setDate(endPH.getDate() - 1);

                        return {
                            title: "Booked",
                            start: startPH.toISOString().split("T")[0],
                            end: endPH.toISOString().split("T")[0],
                            allDay: true,
                            display: "background",
                            backgroundColor: "#f56565",
                            borderColor: "#f56565",
                        };
                    })
                    .filter(event => new Date(event.end) >= todayDate); // ⛔ hide past bookings

                const calendar = new FullCalendar.Calendar(document.getElementById("calendar"), {
                    initialView: "dayGridMonth",
                    height: "auto",
                    aspectRatio: 1.4,
                    headerToolbar: {
                        left: "prev,next today",
                        center: "title",
                        right: ""
                    },
                    timeZone: "Asia/Manila",
                    events: bookedEvents,

                    // ✅ Restrict navigation (no past months)
                    validRange: {
                        start: todayDate.toISOString().split("T")[0],
                        end: "2026-12-31" // Optional: limit max future booking range
                    },

                    // 🩶 Grey out past dates
                    dayCellDidMount: function (info) {
                        const cellDate = new Date(info.date);
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        cellDate.setHours(0, 0, 0, 0);

                        if (cellDate < today) {
                            info.el.style.backgroundColor = "#e9ecef";
                            info.el.style.opacity = "0.7";
                        }
                    },

                    // 🚫 Disable clicks on past dates
                    dateClick: function (info) {
                        const clickedDate = new Date(info.date);
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        clickedDate.setHours(0, 0, 0, 0);

                        if (clickedDate < today) {
                            return; // Ignore past date clicks
                        }

                        console.log("Future date clicked:", clickedDate);
                    }
                });

                calendar.render();
            });
    }

    // 🧭 Debugging logs
    console.log("Local device time:", new Date());
    console.log("Philippine time:", new Date().toLocaleString("en-US", { timeZone: "Asia/Manila" }));
});
</script>



@section('Footer')
@include('Footer')
@endsection
