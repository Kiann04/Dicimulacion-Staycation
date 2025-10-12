@extends('layouts.default')

@section('content')

@section('Header')
    @include('Header')
@endsection

<!-- Modern Hero Section -->
<section class="hero-section">
  <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="5000">

    <!-- Indicators -->
    <div class="carousel-indicators">
      <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
      <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
      <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
    </div>

    <!-- Slides -->
    <div class="carousel-inner">

      <!-- Slide 1 -->
      <div class="carousel-item active">
        <div class="hero-slide" style="background-image: url('{{ asset('assets/sunset.jpg') }}');">
          <div class="hero-overlay"></div>
          <div class="hero-content text-center text-white fade-in">
            <h1 class="fw-bold display-4">Find Your Next Perfect Place</h1>
            <p class="lead">Relax, recharge, and stay in comfort.</p>
          </div>
        </div>
      </div>

      <!-- Slide 2 -->
      <div class="carousel-item">
        <div class="hero-slide" style="background-image: url('{{ asset('assets/Sunlight.jpg') }}');">
          <div class="hero-overlay"></div>
          <div class="hero-content text-center text-white fade-in">
            <h1 class="fw-bold display-4">Experience the Best Staycation</h1>
            <p class="lead">Luxury and peace combined, just for you.</p>
          </div>
        </div>
      </div>

      <!-- Slide 3 -->
      <div class="carousel-item">
        <div class="hero-slide" style="background-image: url('{{ asset('assets/forest.jpg') }}');">
          <div class="hero-overlay"></div>
          <div class="hero-content text-center text-white fade-in">
            <h1 class="fw-bold display-4">Unwind in Beautiful Places</h1>
            <p class="lead">Discover comfort close to home.</p>
          </div>
        </div>
      </div>

    </div>

    <!-- Controls -->
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
      <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
      <span class="carousel-control-next-icon"></span>
    </button>
  </div>
</section>

<!-- Styles -->
<style>
.hero-section {
  position: relative;
  height: 80vh;
  min-height: 440px;
}

.hero-slide {
  position: relative;
  background-size: cover;
  background-position: center;
  height: 80vh;
  min-height: 440px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.hero-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, rgba(0,0,0,0.6), rgba(0,0,0,0.2));
  z-index: 1;
}

.hero-content {
  position: relative;
  z-index: 2;
  max-width: 800px;
  padding: 1rem;
  text-shadow: 0 2px 10px rgba(0,0,0,0.7);
}

.hero-content h1 {
  font-size: 2.8rem;
  letter-spacing: 1px;
}

.hero-content p {
  font-size: 1.2rem;
  font-weight: 300;
  margin-top: 0.5rem;
}

.carousel-control-prev-icon,
.carousel-control-next-icon {
  filter: brightness(0) invert(1);
}

.carousel-indicators [data-bs-target] {
  width: 12px;
  height: 12px;
  border-radius: 50%;
}

.fade-in {
  animation: fadeInUp 1.2s ease-in-out;
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>


<!-- Modern About Us Section -->
<section class="container my-5 py-5" id="about">
  <div class="row align-items-center g-5">
    
    <!-- Image Side -->
    <div class="col-lg-6">
      <div class="about-img-wrapper position-relative rounded-4 overflow-hidden shadow-lg">
        <img src="{{ asset('assets/AboutUs.jpg') }}" class="img-fluid" alt="About Us">
        <div class="overlay"></div>
      </div>
    </div>

    <!-- Text Side -->
    <div class="col-lg-6 fade-in-up">
      <h5 class="text-primary fw-semibold mb-2">About Us</h5>
      <h2 class="fw-bold display-6 mb-4">
        We Provide The Best <br> Place For You
      </h2>
      <p class="text-muted fs-5 mb-3">
        We’re a family-owned staycation business with eight homes, each perfectly placed to enjoy either breathtaking city views or peaceful nature escapes.
      </p>
      <p class="text-muted fs-5 mb-3">
        Whether you’re looking to recharge under the stars, or relax in the calm of nature, our homes are designed to make you feel right at home.
      </p>
      <p class="text-muted fs-5 mb-3">
        Each property is thoughtfully prepared by our family to ensure comfort, style, and unforgettable memories.
      </p>

      <!-- Mini Icons / Stats -->
      <div class="row mt-4 text-center">
        <div class="col">
          <h3 class="fw-bold">Our Homes</h3>
          <p class="text-muted mb-0">Perfectly placed for city and nature escapes</p>
        </div>
        <div class="col">
          <h3 class="fw-bold">Family-Owned</h3>
          <p class="text-muted mb-0">Personalized care in every stay</p>
        </div>
        <div class="col">
          <h3 class="fw-bold">Excellent Feedback</h3>
          <p class="text-muted mb-0">Happy guests all over the world</p>
        </div>
      </div> <!-- Close mini-icons row -->
    </div> <!-- Close text column -->
    
  </div> <!-- Close main row -->
</section>

<!-- Modern Styling -->
<style>
/* Image wrapper */
.about-img-wrapper {
  position: relative;
  height: 100%;
}
.about-img-wrapper img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform .5s ease;
}
.about-img-wrapper:hover img {
  transform: scale(1.05);
}
.about-img-wrapper .overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(0,0,0,0.35), rgba(0,0,0,0));
}

/* Fade-in animation */
.fade-in-up {
  animation: fadeInUp 1.2s ease-in-out;
}
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Adjust spacing for text-only layout */
#about .col-lg-6.fade-in-up {
  padding-bottom: 1rem;
}
</style>

<!-- Featured Artists Section -->
<section class="featured-artists-section py-5 my-5" id="artists">
  <div class="container text-center text-white">
    <h2 class="fw-bold display-6 mb-3">Artists Who Filmed Here</h2>
    <p class="text-light mb-5 fs-5">
      From the romantic film <strong>“Minamahal 100 Bulak Para Kay Stella”</strong>
      — filmed at our beautiful staycation property.
    </p>

    <div class="row g-4 justify-content-center">
      <div class="col-sm-6 col-md-5 col-lg-4">
        <div class="artist-card card bg-transparent border-0 rounded-4 overflow-hidden h-100">
          <div class="artist-img-wrapper">
            <img src="{{ asset('assets/artists/ashtine_olviga.jpg') }}" class="artist-img" alt="Ashtine Olviga">
          </div>
          <div class="card-body text-center">
            <h5 class="fw-bold text-white mb-1">Ashtine Olviga</h5>
            <p class="text-light small mb-1">Singer & Actress</p>
            <p class="fw-semibold small text-primary mb-0">“Minamahal 100 Bulak Para Kay Stella”</p>
          </div>
        </div>
      </div>

      <!-- Artist 1: Andres Muhlach -->
      <div class="col-sm-6 col-md-5 col-lg-4">
        <div class="artist-card card bg-transparent border-0 rounded-4 overflow-hidden h-100">
          <div class="artist-img-wrapper">
            <img src="{{ asset('assets/artists/andres_muhlach.jpg') }}" class="artist-img" alt="Andres Muhlach">
          </div>
          <div class="card-body text-center">
            <h5 class="fw-bold text-white mb-1">Andres Muhlach</h5>
            <p class="text-light small mb-1">Actor</p>
            <p class="fw-semibold small text-primary mb-0">“Minamahal 100 Bulak Para Kay Stella”</p>
          </div>
        </div>
      </div>


    </div>
  </div>
</section>

<!-- Artist Section Styling -->
<style>
/* Background setup */
.featured-artists-section {
  position: relative;
  background-image: url('{{ asset('assets/artists/movie_bg.jpg') }}');
  background-size: cover;
  background-repeat: no-repeat;
  background-position: center 0;
  overflow: hidden;
  border-radius: 0;
  min-height: 600px; /* ensure tall enough to see movement */
  transition: background-position 0.1s linear;
}

/* Overlay */
.featured-artists-section::before {
  content: "";
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  z-index: 1;
}

/* Content layering */
.featured-artists-section .container {
  position: relative;
  z-index: 2;
}

/* Artist image container (centering fix) */
.artist-img-wrapper {
  height: 360px;
  display: flex;
  justify-content: center;
  align-items: center;
  overflow: hidden;
  background-color: #000;
}

/* Artist image */
.artist-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s ease;
  border-bottom: 3px solid #0d6efd;
}
.artist-img:hover {
  transform: scale(1.05);
}

/* Artist card styling */
.artist-card {
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  transition: all 0.3s ease;
}
.artist-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 1rem 2rem rgba(0,0,0,0.4);
}
/* Responsive fix for Ashtine's image (focus on left side) */
.artist-card img[alt="Ashtine Olviga"] {
  object-position: left center !important;
}

/* Responsive fix for Andres Muhlach's image (focus on center-right if needed) */
.artist-card img[alt="Andres Muhlach"] {
  object-position: right center !important;
}

/* Tablet view (≤768px) */
@media (max-width: 768px) {
  .artist-card img[alt="Ashtine Olviga"] {
    height: 300px;
    object-position: 20% center !important;
  }

  .artist-card img[alt="Andres Muhlach"] {
    height: 300px;
    object-position: 80% center !important;
  }
}

/* Mobile view (≤320px) */
@media (max-width: 320px) {
  .artist-card img[alt="Ashtine Olviga"] {
    height: 250px;
    object-position: 15% center !important;
  }

  .artist-card img[alt="Andres Muhlach"] {
    height: 250px;
    object-position: 15% center !important; /* adjust slightly right for balance */
  }
}

</style>

<!-- Info Cards -->
<section class="container my-5 text-center" id="sales">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0 rounded-4 p-4 hover-card">
                <div class="card-body">
                    <div class="icon-circle bg-primary bg-opacity-10 text-primary mb-3 mx-auto">
                        <i class='bx bxs-map-pin fs-1'></i>
                    </div>
                    <h4 class="fw-bold">Stay</h4>
                    <p class="text-muted">
                        Stay in comfort, stay in style. Your perfect getaway starts right here, without going far.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0 rounded-4 p-4 hover-card">
                <div class="card-body">
                    <div class="icon-circle bg-success bg-opacity-10 text-success mb-3 mx-auto">
                        <i class='bx bx-wink-smile fs-1'></i>
                    </div>
                    <h4 class="fw-bold">Enjoy</h4>
                    <p class="text-muted">
                        Enjoy cozy spaces, fresh linens, and the kind of peace that makes you want to stay just a little longer.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0 rounded-4 p-4 hover-card">
                <div class="card-body">
                    <div class="icon-circle bg-warning bg-opacity-10 text-warning mb-3 mx-auto">
                        <i class='bx bxs-user-check fs-1'></i>
                    </div>
                    <h4 class="fw-bold">Relax</h4>
                    <p class="text-muted">
                        Relax and recharge in a space designed for rest, where every detail whispers calm and comfort.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Extra styling -->
<style>
    .icon-circle {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .hover-card {
        transition: all 0.3s ease;
    }
    .hover-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 1rem 2rem rgba(0,0,0,0.15) !important;
    }
</style>


<!-- Properties -->
<section class="container my-5" id="properties">
    <div class="text-center mb-5">
        <span class="badge bg-primary-subtle text-primary px-3 py-2">Recent</span>
        <h2 class="fw-bold mt-3">Our Featured Houses</h2>
        <p class="text-muted mx-auto" style="max-width: 600px;">
            Our houses are warm, quiet, and thoughtfully minimal. With clean design and all the essentials, 
            they offer the perfect setting for restful, easygoing stays.
        </p>
    </div>
    <div class="row g-4">
        @foreach($staycations as $staycation)
            @if($staycation->house_availability === 'available')
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden hover-shadow transition">
                        <div class="position-relative">
                            <img src="{{ asset('storage/' . $staycation->house_image) }}" 
                                 class="card-img-top rounded-top-4" 
                                 alt="{{ $staycation->house_name }}"
                                 style="height: 230px; object-fit: cover;">
                            <span class="badge bg-success position-absolute top-0 end-0 m-3 px-3 py-2">
                                Available
                            </span>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold">{{ $staycation->house_name }}</h5>
                            <p class="text-muted small mb-2">
                                <i class="bi bi-geo-alt-fill text-primary"></i> {{ $staycation->house_location }}
                            </p>
                            <p class="flex-grow-1">{{ $staycation->house_description }}</p>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <h6 class="fw-bold text-primary mb-0">
                                    {{ number_format($staycation->house_price, 2) }} PHP
                                </h6>
                                <a href="{{ url('booking', $staycation->id) }}" 
                                   class="btn btn-outline-primary rounded-pill px-4">
                                    Book Now
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</section>

<!-- Extra styling -->
<style>
    .hover-shadow:hover {
        box-shadow: 0 0.8rem 1.5rem rgba(0, 0, 0, 0.15) !important;
        transform: translateY(-4px);
        transition: all 0.3s ease-in-out;
    }
    .transition {
        transition: all 0.3s ease-in-out;
    }
</style>
<!-- Testimonials Carousel -->
<section class="container my-5" id="featured-reviews">
    <div class="text-center mb-5">
        <h2 class="fw-bold">Featured Reviews</h2>
        <p class="text-muted">What our happy clients say about Dicimulacion Staycation</p>
    </div>

    <div class="reviews-slider">
        <!-- Review Cards -->
        <div class="review-card card h-100 shadow border-0 text-center p-4">
            <img src="{{ asset('assets/bc4aeb512c762d89c6b3e51ff84e3fe78bf32c3b.png') }}" class="rounded-circle mx-auto mb-3" width="80" alt="Darwin">
            <h5 class="fw-bold">Darwin</h5>
            <p class="text-muted small">"The staycation was perfect! The house was clean, spacious, and the view was breathtaking. Highly recommend!"</p>
            <div class="text-warning">
                <i class='bx bxs-star'></i><i class='bx bxs-star'></i>
                <i class='bx bxs-star'></i><i class='bx bxs-star'></i>
                <i class='bx bxs-star'></i>
            </div>
        </div>

        <div class="review-card card h-100 shadow border-0 text-center p-4">
            <img src="{{ asset('assets/Sarah.jpg') }}" class="rounded-circle mx-auto mb-3" width="80" alt="Sarah">
            <h5 class="fw-bold">Sarah</h5>
            <p class="text-muted small">"Amazing experience! The staff were friendly, the rooms were cozy, and everything exceeded our expectations."</p>
            <div class="text-warning">
                <i class='bx bxs-star'></i><i class='bx bxs-star'></i>
                <i class='bx bxs-star'></i><i class='bx bxs-star'></i>
                <i class='bx bxs-star'></i>
            </div>
        </div>

        <div class="review-card card h-100 shadow border-0 text-center p-4">
            <img src="{{ asset('assets/Alex.jpg') }}" class="rounded-circle mx-auto mb-3" width="80" alt="James">
            <h5 class="fw-bold">James</h5>
            <p class="text-muted small">"A truly relaxing staycation. The house had everything we needed, and the view was simply stunning. Will return!"</p>
            <div class="text-warning">
                <i class='bx bxs-star'></i><i class='bx bxs-star'></i>
                <i class='bx bxs-star'></i><i class='bx bxs-star'></i>
                <i class='bx bxs-star'></i>
            </div>
        </div>

        <div class="review-card card h-100 shadow border-0 text-center p-4">
            <img src="{{ asset('assets/test.jpg') }}" class="rounded-circle mx-auto mb-3" width="80" alt="Anna">
            <h5 class="fw-bold">Anna</h5>
            <p class="text-muted small">"Wonderful stay! Everything was neat and organized, and the location is perfect for a weekend getaway."</p>
            <div class="text-warning">
                <i class='bx bxs-star'></i><i class='bx bxs-star'></i>
                <i class='bx bxs-star'></i><i class='bx bxs-star'></i>
                <i class='bx bxs-star'></i>
            </div>
        </div>
    </div>
</section>
<style>
.reviews-slider .review-card img {
    width: 80px;
    height: 80px;
    object-fit: cover; /* Ensures the face fills the circle nicely */
    border-radius: 50%; /* Makes it a circle */
    aspect-ratio: 1/1; /* Forces it to stay square even if the source image isn't */
    border: 3px solid #f8f9fa; /* optional light border */
}
/* Prevent slick carousel overflow */
.reviews-slider {
  overflow-x: hidden !important;
}
.slick-track {
  margin-left: 0 !important;
  margin-right: 0 !important;
}
</style>

<!-- Slick CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css"/>

<!-- jQuery + Slick JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>

<!-- Initialize Slick -->
<script>
$(document).ready(function(){
    $('.reviews-slider').slick({
        slidesToShow: 3,
        slidesToScroll: 1,
        infinite: true,
        autoplay: true,
        autoplaySpeed: 0, // continuous scrolling
        speed: 8000, // adjust for scroll speed
        cssEase: 'linear', // smooth continuous movement
        arrows: false, // modern look
        dots: false,
        pauseOnHover: true,
        responsive: [
            { breakpoint: 992, settings: { slidesToShow: 2 } },
            { breakpoint: 768, settings: { slidesToShow: 1 } }
        ]
    });
});
</script>

<!-- Optional Modern Card CSS -->
<style>
.review-card {
    transition: transform 0.3s, box-shadow 0.3s;
    border-radius: 20px;
    background: #ffffff;
}
.review-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}
</style>


<!-- Styles -->
<style>
.reviews-slider .review-card {
    margin: 0 10px;
}
.slick-slide {
    display: flex !important;
    justify-content: center;
}
.slick-dots li button:before {
    font-size: 12px;
    color: #0d6efd;
}
</style>


<!-- Contact Us -->
<section class="container my-5" id="contact">
    <div class="text-center mb-4">
        <h2 class="fw-bold">Have a Question? <br> Contact Us</h2>
    </div>
    <form class="mx-auto" style="max-width: 600px;" method="POST" action="{{ route('contact.send') }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <input type="email" class="form-control" name="email" placeholder="Your Email" required>
        </div>
        <div class="mb-3">
            <textarea class="form-control" name="message" rows="4" placeholder="Write your message here..." required></textarea>
        </div>
        <div class="mb-3">
            <input type="file" class="form-control" name="attachment" accept="image/*">
        </div>
        <button type="submit" class="btn btn-primary w-100">Send</button>
    </form>
</section>
<script>
  document.addEventListener("scroll", function() {
    const section = document.querySelector(".featured-artists-section");
    if (!section) return;

    // Scroll position
    const scrollTop = window.scrollY;
    const offsetTop = section.offsetTop;
    const sectionHeight = section.offsetHeight;

    // Only move while section is visible in viewport
    if (scrollTop + window.innerHeight > offsetTop && scrollTop < offsetTop + sectionHeight) {
      const distance = scrollTop - offsetTop;
      const moveY = distance * 0.4; // lower number = slower movement
      section.style.backgroundPosition = `center ${moveY}px`;
    }
  });
</script>

@include('partials.chatbot')

@endsection
@section('Footer')
    @include('Footer')
@endsection

