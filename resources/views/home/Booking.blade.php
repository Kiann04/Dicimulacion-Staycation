@extends('layouts.default')

@section('Header')
    @include('Header')
@endsection

<section class="container my-5">
  <div class="row g-4 align-items-start">
    
    <!-- Booking Form -->
    <div class="col-lg-6">
      <div class="card shadow-sm border-0">
        <div class="card-body p-4">
          <h3 class="fw-bold">Booking Form for {{ $staycation->house_name }}</h3>
          <p class="text-muted">Enter the required information to book</p>

          @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
          @endif
          @if(session('message'))
            <div class="alert alert-danger">{!! nl2br(e(session('message'))) !!}</div>
          @endif

          <!-- ✅ Updated Form -->
          <form action="{{ route('booking.preview', $staycation->id) }}" method="POST">
            @csrf
            <div class="mb-3">
              <label class="form-label">Full Name</label>
              <input type="text" name="name" class="form-control" placeholder="Name" required
                @if(Auth::id()) value="{{ Auth::user()->name }}" @endif>
            </div>

            <div class="mb-3">
              <label class="form-label">Contact Number</label>
              <input type="tel" name="phone" class="form-control" placeholder="9123456789" required
                pattern="[0-9]{10}" title="Enter a valid 10-digit Philippine phone number">
            </div>

            <div class="mb-3">
              <label class="form-label">Guests</label>
              <input type="number" name="guest_number" class="form-control" placeholder="Guest/s" required>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label">Date of Arrival</label>
                <input type="date" name="startDate" id="startDate" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Date of Departure</label>
                <input type="date" name="endDate" id="endDate" class="form-control" required>
              </div>
            </div>

            <div id="price-summary" class="border-top pt-3 mb-3" style="display:none;">
              <p>₱<span id="price-per-night">{{ $staycation->house_price }}</span> / night</p>
              <p id="total-price" class="fw-bold text-success"></p>
            </div>

            <div class="form-check mb-3">
              <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
              <label class="form-check-label" for="terms">
                I agree to the <a href="{{ url('/terms') }}" target="_blank">Terms & Conditions</a>
              </label>
            </div>

            @auth
              <button type="submit" class="btn btn-primary w-100 fw-semibold">
                Reserve
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

    <!-- Image Carousel -->
    <div class="col-lg-6">
      <div id="staycationCarousel" class="carousel slide shadow-sm rounded overflow-hidden" data-bs-ride="carousel">
        <div class="carousel-inner">
          <div class="carousel-item active">
            <img src="{{ asset('assets/House1.png') }}" class="d-block w-100 rounded" alt="Staycation Image">
          </div>
          <div class="carousel-item">
            <img src="{{ asset('assets/House2.png') }}" class="d-block w-100 rounded" alt="Staycation Image">
          </div>
          <div class="carousel-item">
            <img src="{{ asset('assets/House3.png') }}" class="d-block w-100 rounded" alt="Staycation Image">
          </div>
          <div class="carousel-item">
            <img src="{{ asset('assets/House5.png') }}" class="d-block w-100 rounded" alt="Staycation Image">
          </div>
        </div>

        <!-- Controls -->
        <button class="carousel-control-prev" type="button" data-bs-target="#staycationCarousel" data-bs-slide="prev">
          <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#staycationCarousel" data-bs-slide="next">
          <span class="carousel-control-next-icon"></span>
        </button>
      </div>
    </div>

  </div>
</section>

<!-- Calendar Section -->
<div class="container my-5">
    <h2 class="fw-bold">Availability</h2>
    <div id="calendar"></div>
</div>

<!-- Info Section -->
<section class="container my-5" id="info">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="p-4 border rounded shadow-sm text-center">
                <i class='bx bxs-home fs-1 text-primary'></i>
                <h4 class="fw-bold mt-2">Accommodation</h4>
                <ul class="list-unstyled text-muted">
                    <li>Kitchen</li>
                    <li>Free parking on premises</li>
                    <li>Private patio or balcony</li>
                    <li>Wifi</li>
                    <li>Bathtub</li>
                    <li>Pets allowed</li>
                </ul>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-4 border rounded shadow-sm text-center">
                <i class='bx bxs-check-square fs-1 text-success'></i>
                <h4 class="fw-bold mt-2">Reminders</h4>
                <p class="text-muted">Check-in after 2:00 PM</p>
                <p class="text-muted">Checkout before 12:00 PM</p>
                <p class="text-muted">6 guests maximum</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-4 border rounded shadow-sm text-center">
                <i class='bx bxs-no-entry fs-1 text-danger'></i>
                <h4 class="fw-bold mt-2">Cancellation Policy</h4>
                <p class="text-muted">We don't accept sudden cancellation</p>
                <p class="text-muted">We can offer rescheduling</p>
            </div>
        </div>
    </div>
</section>

@php $allReviews = $allReviews ?? collect(); @endphp

<section class="container my-5" id="reviews">
    <h2 class="fw-bold mb-4 text-center">What Our Guests Say</h2>

    @if($allReviews->count() > 0)
        <div class="row g-4">
            @foreach($allReviews as $review)
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm border-0 h-100 rounded-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0 fw-semibold">{{ $review->user->name ?? 'Guest' }}</h5>
                                <div class="text-warning">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i class="bx {{ $i <= $review->rating ? 'bxs-star' : 'bx-star' }}"></i>
                                    @endfor
                                </div>
                            </div>

                            <p class="text-muted small mb-1">
                                {{ $review->created_at->format('F d, Y') }}
                                @if($review->booking && $review->booking->staycation)
                                    – <em>{{ $review->booking->staycation->house_name }}</em>
                                @endif
                            </p>

                            <p class="mb-0 text-secondary">{{ $review->comment }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-center text-muted">No reviews have been submitted yet for this staycation.</p>
    @endif
</section>




<!-- FullCalendar -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const startInput = document.getElementById("startDate");
    const endInput = document.getElementById("endDate");
    const priceSummary = document.getElementById("price-summary");
    const totalPriceElem = document.getElementById("total-price");
    const pricePerNight = parseFloat(document.getElementById("price-per-night").innerText);
    const form = document.querySelector("form");

    // ===== Set minimum dates =====
    const today = new Date().toISOString().split("T")[0];
    if (startInput) startInput.min = today;
    if (endInput) endInput.min = today;

    // ===== Update minimum departure date based on arrival =====
    startInput.addEventListener("change", function() {
        if (this.value) {
            const arrival = new Date(this.value);
            const minDeparture = new Date(arrival);
            minDeparture.setDate(arrival.getDate() + 1); // strictly next day

            const yyyy = minDeparture.getFullYear();
            const mm = String(minDeparture.getMonth() + 1).padStart(2, "0");
            const dd = String(minDeparture.getDate()).padStart(2, "0");
            const minDateStr = `${yyyy}-${mm}-${dd}`;

            endInput.min = minDateStr;

            // Reset end date if it's before the new min
            if (endInput.value && new Date(endInput.value) < minDeparture) {
                endInput.value = minDateStr;
            }

            calculatePrice();
        }
    });

    // ===== Calculate price =====
    function calculatePrice() {
        if (startInput.value && endInput.value) {
            const start = new Date(startInput.value);
            const end = new Date(endInput.value);

            if (end > start) { // strictly greater than start
                const nights = Math.floor((end - start) / (1000 * 60 * 60 * 24));
                const total = nights * pricePerNight;
                priceSummary.style.display = "block";
                totalPriceElem.textContent = `Total for ${nights} night(s): ₱${total.toLocaleString()}`;
            } else {
                priceSummary.style.display = "none";
            }
        } else {
            priceSummary.style.display = "none";
        }
    }

    startInput.addEventListener("change", calculatePrice);
    endInput.addEventListener("change", calculatePrice);

    // ===== Prevent form submission if dates are invalid =====
    if (form) {
        form.addEventListener("submit", function(e) {
            const start = new Date(startInput.value);
            const end = new Date(endInput.value);

            if (end <= start) {
                e.preventDefault();
                alert("Departure date must be at least one day after arrival date.");
                return false;
            }
        });
    }

    // ===== FullCalendar with booked nights (exclude checkout day) =====
    const staycationId = "{{ $staycation->id }}";
    if (document.getElementById("calendar")) {
        fetch(`/events/${staycationId}`)
            .then(res => res.json())
            .then(events => {
                const bookedEvents = events.map(event => {
                    const start = event.start;
                    const end = new Date(event.end);
                    end.setDate(end.getDate() - 1); // exclude checkout day

                    return {
                        title: "Booked",
                        start: start,
                        end: end.toISOString().split("T")[0],
                        allDay: true,
                        display: 'background',
                        backgroundColor: '#f56565',
                        borderColor: '#f56565'
                    };
                });

                const calendar = new FullCalendar.Calendar(document.getElementById("calendar"), {
                    initialView: "dayGridMonth",
                    height: "auto",
                    aspectRatio: 1.4,
                    headerToolbar: {
                        left: "prev,next today",
                        center: "title",
                        right: ""
                    },
                    events: bookedEvents
                });

                calendar.render();
            });
    }
});
</script>

@section('Footer')
    @include('Footer')
@endsection
