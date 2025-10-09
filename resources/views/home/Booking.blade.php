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
{{-- Modern Services Section --}}
<section class="services-section py-5 bg-gradient" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="text-center mb-5">
                    <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill mb-3">Amenities & Services</span>
                    <h2 class="display-4 fw-bold text-dark mb-3">Kinds of Service Offered</h2>
                    <p class="lead text-muted fs-5">
                        {{ $staycation->house_name }} offers the following services and amenities to ensure a complete staycation experience.
                    </p>
                </div>
                
                <div class="row g-4">
                    {{-- Scenic Views --}}
                    <div class="col-md-6 col-lg-4">
                        <div class="service-card h-100 position-relative overflow-hidden rounded-4 shadow-lg border-0 transition-all" style="transition: all 0.3s ease; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);">
                            <div class="card-body text-center p-5 d-flex flex-column justify-content-center align-items-center">
                                <div class="icon mb-4 position-relative">
                                    <i class="fas fa-mountain fa-4x text-primary" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"></i>
                                </div>
                                <h5 class="card-title fw-bold text-dark mb-3 fs-5">Scenic Views</h5>
                                <p class="card-text text-muted mb-0">City Skyline, Lake, and Mountain</p>
                            </div>
                            <div class="service-overlay position-absolute top-0 left-0 w-100 h-100 d-none"></div>
                        </div>
                    </div>
                    
                    {{-- Fully-equipped Kitchen --}}
                    <div class="col-md-6 col-lg-4">
                        <div class="service-card h-100 position-relative overflow-hidden rounded-4 shadow-lg border-0 transition-all" style="transition: all 0.3s ease; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);">
                            <div class="card-body text-center p-5 d-flex flex-column justify-content-center align-items-center">
                                <div class="icon mb-4 position-relative">
                                    <i class="fas fa-utensils fa-4x text-primary" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"></i>
                                </div>
                                <h5 class="card-title fw-bold text-dark mb-3 fs-5">Fully-equipped Kitchen</h5>
                                <p class="card-text text-muted mb-0">Dining table, Rice cooker, Refrigerator, Kettle, Stove, and Utensils</p>
                            </div>
                            <div class="service-overlay position-absolute top-0 left-0 w-100 h-100 d-none"></div>
                        </div>
                    </div>
                    
                    {{-- Essentials --}}
                    <div class="col-md-6 col-lg-4">
                        <div class="service-card h-100 position-relative overflow-hidden rounded-4 shadow-lg border-0 transition-all" style="transition: all 0.3s ease; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);">
                            <div class="card-body text-center p-5 d-flex flex-column justify-content-center align-items-center">
                                <div class="icon mb-4 position-relative">
                                    <i class="fas fa-soap fa-4x text-primary" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"></i>
                                </div>
                                <h5 class="card-title fw-bold text-dark mb-3 fs-5">Essentials</h5>
                                <p class="card-text text-muted mb-0">Towels, Bed sheets, Extra pillows, Blankets, Cleaning products, Body soap, Shampoo, and Toilet paper</p>
                            </div>
                            <div class="service-overlay position-absolute top-0 left-0 w-100 h-100 d-none"></div>
                        </div>
                    </div>
                    
                    {{-- In-room Entertainment --}}
                    <div class="col-md-6 col-lg-4">
                        <div class="service-card h-100 position-relative overflow-hidden rounded-4 shadow-lg border-0 transition-all" style="transition: all 0.3s ease; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);">
                            <div class="card-body text-center p-5 d-flex flex-column justify-content-center align-items-center">
                                <div class="icon mb-4 position-relative">
                                    <i class="fas fa-tv fa-4x text-primary" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"></i>
                                </div>
                                <h5 class="card-title fw-bold text-dark mb-3 fs-5">In-room Entertainment</h5>
                                <p class="card-text text-muted mb-0">TV, Books, Board games</p>
                            </div>
                            <div class="service-overlay position-absolute top-0 left-0 w-100 h-100 d-none"></div>
                        </div>
                    </div>
                    
                    {{-- Outdoor Amenities --}}
                    <div class="col-md-6 col-lg-4">
                        <div class="service-card h-100 position-relative overflow-hidden rounded-4 shadow-lg border-0 transition-all" style="transition: all 0.3s ease; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);">
                            <div class="card-body text-center p-5 d-flex flex-column justify-content-center align-items-center">
                                <div class="icon mb-4 position-relative">
                                    <i class="fas fa-tree fa-4x text-primary" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"></i>
                                </div>
                                <h5 class="card-title fw-bold text-dark mb-3 fs-5">Outdoor Amenities</h5>
                                <p class="card-text text-muted mb-0">Patio, BBQ Grill, Dining area</p>
                            </div>
                            <div class="service-overlay position-absolute top-0 left-0 w-100 h-100 d-none"></div>
                        </div>
                    </div>
                    
                    {{-- Pool Access --}}
                    <div class="col-md-6 col-lg-4">
                        <div class="service-card h-100 position-relative overflow-hidden rounded-4 shadow-lg border-0 transition-all" style="transition: all 0.3s ease; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);">
                            <div class="card-body text-center p-5 d-flex flex-column justify-content-center align-items-center">
                                <div class="icon mb-4 position-relative">
                                    <i class="fas fa-swimming-pool fa-4x text-primary" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"></i>
                                </div>
                                <h5 class="card-title fw-bold text-dark mb-3 fs-5">Pool Access</h5>
                                <p class="card-text text-muted mb-0">Enjoy refreshing swims anytime</p>
                            </div>
                            <div class="service-overlay position-absolute top-0 left-0 w-100 h-100 d-none"></div>
                        </div>
                    </div>
                    
                    {{-- Free Parking --}}
                    <div class="col-md-6 col-lg-4">
                        <div class="service-card h-100 position-relative overflow-hidden rounded-4 shadow-lg border-0 transition-all" style="transition: all 0.3s ease; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);">
                            <div class="card-body text-center p-5 d-flex flex-column justify-content-center align-items-center">
                                <div class="icon mb-4 position-relative">
                                    <i class="fas fa-parking fa-4x text-primary" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"></i>
                                </div>
                                <h5 class="card-title fw-bold text-dark mb-3 fs-5">Free Parking</h5>
                                <p class="card-text text-muted mb-0">On-site and street</p>
                            </div>
                            <div class="service-overlay position-absolute top-0 left-0 w-100 h-100 d-none"></div>
                        </div>
                    </div>
                    
                    {{-- Pet-friendly accommodations --}}
                    <div class="col-md-6 col-lg-4">
                        <div class="service-card h-100 position-relative overflow-hidden rounded-4 shadow-lg border-0 transition-all" style="transition: all 0.3s ease; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);">
                            <div class="card-body text-center p-5 d-flex flex-column justify-content-center align-items-center">
                                <div class="icon mb-4 position-relative">
                                    <i class="fas fa-paw fa-4x text-primary" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"></i>
                                </div>
                                <h5 class="card-title fw-bold text-dark mb-3 fs-5">Pet-friendly Accommodations</h5>
                                <p class="card-text text-muted mb-0">Bring your furry friends along</p>
                            </div>
                            <div class="service-overlay position-absolute top-0 left-0 w-100 h-100 d-none"></div>
                        </div>
                    </div>
                    
                    {{-- Breakfast provided --}}
                    <div class="col-md-6 col-lg-4">
                        <div class="service-card h-100 position-relative overflow-hidden rounded-4 shadow-lg border-0 transition-all" style="transition: all 0.3s ease; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);">
                            <div class="card-body text-center p-5 d-flex flex-column justify-content-center align-items-center">
                                <div class="icon mb-4 position-relative">
                                    <i class="fas fa-coffee fa-4x text-primary" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"></i>
                                </div>
                                <h5 class="card-title fw-bold text-dark mb-3 fs-5">Breakfast Provided</h5>
                                <p class="card-text text-muted mb-0">Start your day with a complimentary meal</p>
                            </div>
                            <div class="service-overlay position-absolute top-0 left-0 w-100 h-100 d-none"></div>
                        </div>
                    </div>
                    
                    {{-- Long-term stay allowed --}}
                    <div class="col-md-6 col-lg-4">
                        <div class="service-card h-100 position-relative overflow-hidden rounded-4 shadow-lg border-0 transition-all" style="transition: all 0.3s ease; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);">
                            <div class="card-body text-center p-5 d-flex flex-column justify-content-center align-items-center">
                                <div class="icon mb-4 position-relative">
                                    <i class="fas fa-calendar-check fa-4x text-primary" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"></i>
                                </div>
                                <h5 class="card-title fw-bold text-dark mb-3 fs-5">Long-term Stay Allowed</h5>
                                <p class="card-text text-muted mb-0">(28+ days)</p>
                            </div>
                            <div class="service-overlay position-absolute top-0 left-0 w-100 h-100 d-none"></div>
                        </div>
                    </div>
                    
                    {{-- Home Safety --}}
                    <div class="col-md-6 col-lg-4">
                        <div class="service-card h-100 position-relative overflow-hidden rounded-4 shadow-lg border-0 transition-all" style="transition: all 0.3s ease; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);">
                            <div class="card-body text-center p-5 d-flex flex-column justify-content-center align-items-center">
                                <div class="icon mb-4 position-relative">
                                    <i class="fas fa-shield-alt fa-4x text-primary" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"></i>
                                </div>
                                <h5 class="card-title fw-bold text-dark mb-3 fs-5">Home Safety</h5>
                                <p class="card-text text-muted mb-0">Fire extinguisher</p>
                            </div>
                            <div class="service-overlay position-absolute top-0 left-0 w-100 h-100 d-none"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Modern Hover Effects for Service Cards */
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

    <div class="row g-4 justify-content-center">
        @forelse($reviews as $review)
        <div class="col-sm-12 col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm rounded-4 p-4 position-relative hover-shadow transition">
                
                <!-- Profile Picture -->
                <div class="d-flex justify-content-center mb-3">
                    <img src="{{ $review->user && $review->user->photo ? asset($review->user->photo) : asset('Assets/default.png') }}" 
                         class="rounded-circle shadow-sm" width="80" height="80" 
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

    // ✅ Create a live Philippine time display for debugging
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

    // ✅ Force timezone to Asia/Manila for all date operations
    const nowInPH = new Date().toLocaleString("en-US", { timeZone: "Asia/Manila" });
    const today = new Date(nowInPH).toISOString().split("T")[0];

    // Set minimum selectable dates
    if (startInput) startInput.min = today;
    if (endInput) endInput.min = today;

    // 🧮 Price calculation
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
                    totalPriceElem.textContent = `Total for ${nights} night${nights > 1 ? 's' : ''} (₱${extraFee.toLocaleString()} extra for ${extraGuests} guest${extraGuests > 1 ? 's' : ''}): ₱${total.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
                } else {
                    totalPriceElem.textContent = `Total for ${nights} night${nights > 1 ? 's' : ''}: ₱${total.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
                }

                priceSummary.style.display = "block";
            } else {
                priceSummary.style.display = "none";
            }
        } else {
            priceSummary.style.display = "none";
        }
    }

    // Handle date changes
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

    // Validate on submit
    if (form) {
        form.addEventListener("submit", function (e) {
            const start = new Date(startInput.value);
            const end = new Date(endInput.value);
            if (end <= start) {
                e.preventDefault();
                alert("Departure date must be at least one day after arrival date.");
            }
        });
    }

    // 🗓️ FullCalendar with PH timezone
    const staycationId = "{{ $staycation->id }}";
    if (document.getElementById("calendar")) {
        fetch(`/events/${staycationId}`)
            .then(res => res.json())
            .then(events => {
                const bookedEvents = events.map(event => {
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
                    timeZone: "Asia/Manila",
                    events: bookedEvents
                });
                calendar.render();
            });
    }

    // 🧭 Debugging console logs
    console.log("Local device time:", new Date());
    console.log("Philippine time:", new Date().toLocaleString("en-US", { timeZone: "Asia/Manila" }));
});
</script>



@section('Footer')
@include('Footer')
@endsection
