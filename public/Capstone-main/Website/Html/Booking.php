<?php include '../../Includes/Header.php'; ?>

<div class="booking container">
    <div class="booking-container">
        <h2>Booking Form</h2> 
        <p>Enter the required information<br>to book</p>

        <form action="">
            <span>Enter your name:</span>
            <input type="text" name="" id="" placeholder="Name" required>
            <span>Enter contact information:</span>
            <input type="tel" name="" id="" placeholder="Phone Number" required>
            <span>How many guests:</span>
            <input type="number" name="" id="" placeholder="Guest/s"  required>
            <span>Date of arrival:</span>
            <input type="date" name="" id="" placeholder="Arrival" required>
            <span>Date of departure:</span>
            <input type="date" name="" id="" placeholder="Departure" required>
            <input type="submit" value="Book" class="buttom">
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
// Image Slider
const slides = document.querySelectorAll('.slide');
const slider = document.querySelector('.slider');
const buttons = document.querySelectorAll('.slider-link');

let currentIndex = 0;
const totalSlides = slides.length;

function goToSlide(index) {
    const slideWidth = slides[index].clientWidth;
    slider.scrollTo({
        left: slideWidth * index,
        behavior: 'smooth'
    });
}

setInterval(() => {
  currentIndex = (currentIndex + 1) % totalSlides;
  goToSlide(currentIndex);
}, 4000);

buttons.forEach(button => {
  button.addEventListener('click', (e) => {
    e.preventDefault();
    const slideIndex = parseInt(button.getAttribute('data-slide'));
    currentIndex = slideIndex;
    goToSlide(currentIndex);
  });
});

// Calendar
document.addEventListener('DOMContentLoaded', function() {
  var calendarEl = document.getElementById('calendar');

  var calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    height: "auto",
    aspectRatio: 1.4,
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: ''
    },
    events: [
      { title: 'Booked', start: '2025-08-18', display: 'background', className: 'booked-date' },
      { title: 'Booked', start: '2025-08-19', display: 'background', className: 'booked-date' },
      { title: 'Available', start: '2025-08-20', display: 'background', className: 'available-date' },
      { title: 'Available', start: '2025-08-21', display: 'background', className: 'available-date' }
    ]
  });

  calendar.render();
});
</script>

<?php include '../../Includes/Footer.php'; ?>
