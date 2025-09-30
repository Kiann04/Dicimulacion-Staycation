@extends('layouts.default')

@section('Header')
    @include('Header')
@endsection

<div class="container my-5">
    <div class="row g-4">
        <!-- Booking Form -->
        <div class="col-lg-6">
            <div class="card shadow-lg border-0">
                <div class="card-body p-4">
                    <h2 class="fw-bold mb-3">
                        {!! nl2br(e("Booking Form for\n" . $staycation->house_name)) !!}
                    </h2>

                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('message'))
                        <div class="alert alert-danger">
                            {!! nl2br(e(session('message'))) !!}
                        </div>
                    @endif

                    <p class="text-muted">Enter the required information to book</p>

                    <form action="{{ url('add_booking', $staycation->id) }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Name" required
                                   @if(Auth::id()) value="{{ Auth::user()->name }}" @endif>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="tel" name="phone" class="form-control"
                                   placeholder="9123456789"
                                   pattern="[0-9]{10}"
                                   title="Enter a valid 10-digit Philippine phone number" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Guests</label>
                            <input type="number" name="guest_number" class="form-control" placeholder="Guest/s" required>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Date of Arrival</label>
                                <input type="date" name="startDate" id="startDate" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date of Departure</label>
                                <input type="date" name="endDate" id="endDate" class="form-control" required>
                            </div>
                        </div>

                        <div id="price-summary" class="alert alert-light mt-3 d-none">
                            <p class="mb-1">₱<span id="price-per-night">{{ $staycation->house_price }}</span> / night</p>
                            <p id="total-price" class="fw-bold text-success mb-0"></p>
                        </div>

                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" name="terms" required>
                            <label class="form-check-label">
                                I agree to the
                                <a href="{{ url('/terms') }}" target="_blank">Terms & Conditions</a>
                            </label>
                        </div>

                        <div class="mt-4">
                            @auth
                                <button type="submit" class="btn btn-primary w-100">Reserve</button>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-secondary w-100">Please log in to reserve</a>
                            @endauth
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Image Slider -->
        <div class="col-lg-6">
            <div id="bookingCarousel" class="carousel slide rounded shadow overflow-hidden" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <img src="../assets/House1.png" class="d-block w-100" alt="">
                    </div>
                    <div class="carousel-item">
                        <img src="../assets/House2.png" class="d-block w-100" alt="">
                    </div>
                    <div class="carousel-item">
                        <img src="../assets/House3.png" class="d-block w-100" alt="">
                    </div>
                    <div class="carousel-item">
                        <img src="../assets/House4.png" class="d-block w-100" alt="">
                    </div>
                    <div class="carousel-item">
                        <img src="../assets/House5.png" class="d-block w-100" alt="">
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#bookingCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#bookingCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Calendar Section -->
    <div class="mt-5">
        <h2 class="fw-bold mb-3">Availability</h2>
        <div class="card shadow-sm p-3">
            <div id="calendar"></div>
        </div>
    </div>

    <!-- Info Section -->
    <section class="row text-center g-4 mt-5">
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <i class="bx bxs-home fs-1 text-primary mb-3"></i>
                    <h5 class="fw-bold">Accommodation</h5>
                    <p class="text-muted">Kitchen, Free Parking, Balcony, Wifi, Bathtub, Pets Allowed</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <i class="bx bxs-check-square fs-1 text-success mb-3"></i>
                    <h5 class="fw-bold">Reminders</h5>
                    <p class="text-muted">Check-in after 2:00 PM <br> Checkout before 12:00 PM <br> Max 6 guests</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <i class="bx bxs-no-entry fs-1 text-danger mb-3"></i>
                    <h5 class="fw-bold">Cancellation Policy</h5>
                    <p class="text-muted">No sudden cancellation <br> Rescheduling available</p>
                </div>
            </div>
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    // Date Restriction
    const today = new Date().toISOString().split("T")[0];
    document.getElementById("startDate").min = today;
    document.getElementById("endDate").min = today;

    // Price Calculation
    const startInput = document.getElementById("startDate");
    const endInput = document.getElementById("endDate");
    const priceSummary = document.getElementById("price-summary");
    const totalPriceElem = document.getElementById("total-price");
    const pricePerNight = parseFloat(document.getElementById("price-per-night").innerText);

    function calculatePrice() {
        if (startInput.value && endInput.value) {
            const start = new Date(startInput.value);
            const end = new Date(endInput.value);

            if (end >= start) {
                const days = Math.floor((end - start) / (1000 * 60 * 60 * 24)) + 1;
                const total = days * pricePerNight;

                priceSummary.classList.remove("d-none");
                totalPriceElem.textContent = `Total for ${days} night(s): ₱${total.toLocaleString()}`;
            } else {
                priceSummary.classList.add("d-none");
            }
        }
    }

    startInput.addEventListener("change", calculatePrice);
    endInput.addEventListener("change", calculatePrice);

    // Calendar
    const staycationId = "{{ $staycation->id }}";
    if (document.getElementById("calendar")) {
        const calendar = new FullCalendar.Calendar(document.getElementById("calendar"), {
            initialView: "dayGridMonth",
            height: "auto",
            aspectRatio: 1.4,
            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: ""
            },
            events: `/events/${staycationId}`
        });
        calendar.render();
    }
});
</script>
