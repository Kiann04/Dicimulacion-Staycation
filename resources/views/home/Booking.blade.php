@extends('layouts.default')

@section('Header')
    @include('Header')
@endsection

<section class="container my-5 pt-5">
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

          <form action="{{ route('booking.preview', $staycation->id) }}" method="POST">
            @csrf

            <div class="mb-3">
              <label class="form-label">Full Name</label>
              <input type="text" name="name" class="form-control" placeholder="Name" required
                value="{{ old('name', Auth::user()->name ?? '') }}">
            </div>

            <div class="mb-3">
              <label class="form-label">Contact Number</label>
              <input type="tel" name="phone" class="form-control" placeholder="9123456789" required
                pattern="[0-9]{10}" title="Enter a valid 10-digit Philippine phone number"
                value="{{ old('phone', Auth::user()->phone ?? '') }}">
            </div>

            <div class="mb-3">
              <label class="form-label">Guests</label>
              <input type="number" name="guest_number" class="form-control" placeholder="Guest/s" required
                value="{{ old('guest_number') }}">
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label">Date of Arrival</label>
                <input type="date" name="startDate" id="startDate" class="form-control" required
                  value="{{ old('startDate') }}">
              </div>
              <div class="col-md-6">
                <label class="form-label">Date of Departure</label>
                <input type="date" name="endDate" id="endDate" class="form-control" required
                  value="{{ old('endDate') }}">
              </div>
            </div>

            <!-- Price Summary -->
            <div id="price-summary" class="border-top pt-3 mb-3" style="display:none;">
              <p>₱<span id="price-per-night">{{ $staycation->house_price }}</span> / night</p>
              <p id="total-price" class="fw-bold text-success"></p>
            </div>

            <!-- Terms -->
            <div class="form-check mb-3">
              <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
              <label class="form-check-label" for="terms">
                I agree to the <a href="{{ url('/terms') }}" target="_blank">Terms & Conditions</a>
              </label>
            </div>

            <!-- Submit -->
            @auth
              <button type="submit" class="btn btn-primary w-100 fw-semibold">
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

    <!-- Image Carousel -->
    <div class="col-lg-6">
      <div id="staycationCarousel" class="carousel slide shadow-sm rounded overflow-hidden" data-bs-ride="carousel">
        <div class="carousel-inner">
          @foreach(['House1.png','House2.png','House3.png','House5.png'] as $i => $img)
            <div class="carousel-item {{ $i === 0 ? 'active' : '' }}">
              <img src="{{ asset('assets/'.$img) }}" class="d-block w-100 rounded" alt="Staycation Image">
            </div>
          @endforeach
        </div>
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

<!-- Reviews Section -->
<section class="container my-5" id="reviews">
    <h2 class="fw-bold mb-4 text-center">What Our Guests Say</h2>
    @php $allReviews = $allReviews ?? collect(); @endphp

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

    // Minimum dates
    const today = new Date().toISOString().split("T")[0];
    if (startInput) startInput.min = today;
    if (endInput) endInput.min = today;

    function calculatePrice(){
        if(startInput.value && endInput.value){
            const start = new Date(startInput.value);
            const end = new Date(endInput.value);
            if(end > start){
                const nights = Math.floor((end - start)/(1000*60*60*24));
                const total = nights * pricePerNight;
                priceSummary.style.display = "block";
                totalPriceElem.textContent = `Total for ${nights} night${nights>1?'s':''}: ₱${total.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}`;
            } else {
                priceSummary.style.display = "none";
            }
        } else {
            priceSummary.style.display = "none";
        }
    }

    startInput.addEventListener("change", function(){
        const arrival = new Date(this.value);
        const minDeparture = new Date(arrival);
        minDeparture.setDate(arrival.getDate() + 1);
        endInput.min = minDeparture.toISOString().split("T")[0];
        if(endInput.value && new Date(endInput.value) < minDeparture){
            endInput.value = endInput.min;
        }
        calculatePrice();
    });

    endInput.addEventListener("change", calculatePrice);

    if(form){
        form.addEventListener("submit", function(e){
            const start = new Date(startInput.value);
            const end = new Date(endInput.value);
            if(end <= start){
                e.preventDefault();
                alert("Departure date must be at least one day after arrival date.");
            }
        });
    }

    // FullCalendar - booked nights (exclude checkout)
    const staycationId = "{{ $staycation->id }}";
    if(document.getElementById("calendar")){
        fetch(`/events/${staycationId}`)
        .then(res => res.json())
        .then(events => {
            const bookedEvents = events.map(event => {
                const start = event.start;
                const end = new Date(event.end);
                end.setDate(end.getDate() - 1); // exclude checkout
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
                headerToolbar: { left:'prev,next today', center:'title', right:'' },
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
