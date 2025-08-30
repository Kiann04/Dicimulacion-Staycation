@extends('layouts.default')

@section('Header')
    @include('Header')
@endsection

<div class="booking container">
    <div class="booking-container">
        <h2>{!! nl2br(e("Booking Form for\n" . $staycation->house_name)) !!}</h2>
            @if(session('success'))
                <div style="color: red; font-weight: bold; margin-bottom: 10px;">
                {{ session('success') }}
                </div>
            @endif
            @if(session('message'))
                <div style="color: red; font-weight: bold; margin-bottom: 10px;">
                {!! nl2br(e(session('message'))) !!}
                </div>
            @endif
        <p>Enter the required information<br>to book</p>

        <form action="{{url('add_booking',$staycation->id)}}" method="POST">
            @csrf
            <span>Enter your name:</span>
            <input type="text" name="name"  placeholder="Name" required
            @if(Auth::id()) value="{{ Auth::user()->name }}"
            @endif>
            <span>Enter contact information:</span>
            <input type="number" name="phone" id="" placeholder="Phone Number" required>
            <span>How many guests:</span>
            <input type="number" name="guest_number" id="" placeholder="Guest/s"  required>
            <span>Date of arrival:</span>
            <input type="date" name="startDate" id="startDate" placeholder="Arrival" required>
            <span>Date of departure:</span>
            <input type="date" name="endDate" id="endDate" placeholder="Departure" required>
            <div id="price-summary" style="display:none; margin-top: 15px; font-weight: bold; border-top: 1px solid #ccc; padding-top: 10px;">
    <p>₱<span id="price-per-night">{{ $staycation->house_price }}</span> / night</p>
    <p id="total-price" style="font-size: 18px; color: green;"></p>
</div>
<input type="submit" value="Reserve" class="buttom">
        </form>
    </div>

    <!-- Slider Section -->
    <section class="container-pics">
        <div class="slider-wrapper">
            <div class="slider">
                <img id="slide-1" class="slide" src="../Assets/House1.png" alt="">
                <img id="slide-2" class="slide" src="../Assets/House2.png" alt="">
                <img id="slide-3" class="slide" src="../Assets/House3.png" alt="">
                <img id="slide-4" class="slide" src="../Assets/House4.png" alt="">
                <img id="slide-5" class="slide" src="../Assets/House5.png" alt="">
            </div>
            <div class="slider-nav">
                <button data-slide="0" class="slider-link"></button>
                <button data-slide="1" class="slider-link"></button>
                <button data-slide="2" class="slider-link"></button>
                <button data-slide="3" class="slider-link"></button>
                <button data-slide="4" class="slider-link"></button>
            </div>
        </div>
    </section>
</div>

<!-- Calendar Section -->
<div class="calendar-container container">
    <h2>Availability</h2>
    <div id="calendar"></div>
</div>

<section class="info container" id="info">
    <div class="box">
        <i class='bx bxs-home'></i>
        <h3>Accomodation</h3>
        <p>Kitchen</p>
        <p>Free parking on premises</p>
        <p>Private patio or balcony</p>
        <p>Wifi</p>
        <p>Bathtub</p>
        <p>Pets allowed</p>
    </div>

    <div class="box">
        <i class='bx bxs-check-square'></i>
        <h3>Reminders</h3>
        <p>Check-in after 2:00 PM</p>
        <p>Checkout before 12:00 PM</p>
        <p>6 guests maximum.</p>
    </div>

    <div class="box">
        <i class='bx bxs-no-entry'></i>
        <h3>Cancellation policy</h3>
        <p>We don't accept sudden cancellation</p>
        <p>We can offer rescheduling</p>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // ====== Date Picker Restriction ======
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, "0");
    const day = String(today.getDate()).padStart(2, "0");
    const minDate = `${year}-${month}-${day}`;

    const startDate = document.getElementById("startDate");
    const endDate = document.getElementById("endDate");

    if (startDate) startDate.min = minDate;
    if (endDate) endDate.min = minDate;

    // ====== Image Slider ======
    const slides = document.querySelectorAll(".slide");
    const slider = document.querySelector(".slider");
    const buttons = document.querySelectorAll(".slider-link");

    let currentIndex = 0;
    const totalSlides = slides.length;

    function goToSlide(index) {
        const slideWidth = slides[0].clientWidth;
        slider.scrollTo({
            left: slideWidth * index,
            behavior: "smooth"
        });
    }

    // Auto-slide every 4 seconds
    setInterval(() => {
        currentIndex = (currentIndex + 1) % totalSlides;
        goToSlide(currentIndex);
    }, 4000);

    // Button navigation
    buttons.forEach((button) => {
        button.addEventListener("click", (e) => {
            e.preventDefault();
            currentIndex = parseInt(button.getAttribute("data-slide"), 10);
            goToSlide(currentIndex);
        });
    });
    // ====== Airbnb-style Price Calculation ======
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
            const days = Math.floor((end - start) / (1000 * 60 * 60 * 24)) + 1; // Include last day
            const total = days * pricePerNight;

            priceSummary.style.display = "block";
            totalPriceElem.textContent = `Total for ${days} night(s): ₱${total.toLocaleString()}`;
        } else {
            priceSummary.style.display = "none";
        }
    }
}

startInput.addEventListener("change", calculatePrice);
endInput.addEventListener("change", calculatePrice);

    // ====== Calendar ======
        const staycationId = "{{ $staycation->id }}"; // Example from Blade

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
            events: `/events/${staycationId}` // pass staycation id to backend
        });

        calendar.render();
    }
    });
</script>