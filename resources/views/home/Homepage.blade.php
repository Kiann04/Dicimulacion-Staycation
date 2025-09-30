@extends('layouts.default')

@section('content')

@section('Header')
    @include('Header')
@endsection
<!-- Hero Section -->
<section class="container my-5">
    <div id="heroCarousel" class="carousel slide rounded overflow-hidden shadow" data-bs-ride="carousel">
        <div class="carousel-inner" style="height: 440px;">

            <!-- Slide 1 -->
            <div class="carousel-item active text-white d-flex align-items-center"
                 style="background: url('{{ asset('assets/sunset.jpg') }}') center/cover no-repeat; height: 440px;">
                <div class="p-5">
                    <h1 class="fw-bold">Find Your Next<br>Perfect Place To<br>Relax</h1>
                    <a href="{{ url('register') }}" class="btn btn-primary mt-3">Sign Up</a>
                </div>
            </div>

            <!-- Slide 2 -->
            <div class="carousel-item text-white d-flex align-items-center"
                 style="background: url('{{ asset('assets/bg.jpg') }}') center/cover no-repeat; height: 440px;">
                <div class="p-5">
                    <h1 class="fw-bold">Experience the<br>Best Staycation<br>With Us</h1>
                    <a href="{{ url('register') }}" class="btn btn-primary mt-3">Get Started</a>
                </div>
            </div>

            <!-- Slide 3 -->
            <div class="carousel-item text-white d-flex align-items-center"
                 style="background: url('{{ asset('assets/bg2.jpg') }}') center/cover no-repeat; height: 440px;">
                <div class="p-5">
                    <h1 class="fw-bold">Unwind in<br>Beautiful Places<br>Close to You</h1>
                    <a href="{{ url('register') }}" class="btn btn-primary mt-3">Join Now</a>
                </div>
            </div>

        </div>

        <!-- Carousel controls -->
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>
</section>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- About Us -->
<section class="container my-5" id="about">
    <div class="row align-items-center g-4">
        <div class="col-md-6">
            <img src="{{ asset('assets/AboutUs.jpg') }}" class="img-fluid rounded shadow-sm" alt="About Us">
        </div>
        <div class="col-md-6">
            <h5 class="text-primary">About Us</h5>
            <h2 class="fw-bold">We Provide The Best<br>Place For You</h2>
            <p>We’re a family-owned staycation business with eight homes, all located with beautiful city lights or surrounded by peaceful nature.</p>
            <p>Whether you want to enjoy the city lights or relax in the calm of nature, we’ve created spaces where you can slow down and feel right at home.</p>
            <p>Each of our homes is carefully designed and cared for by our family to make sure your stay is comfortable and memorable.</p>
            <p>We believe in treating guests like friends, with thoughtful details and a personal touch that make all the difference. Come stay with us—and be a part of our story.</p>
            <a href="#" class="btn btn-outline-primary mt-3">Learn More</a>
        </div>
    </div>
</section>

<!-- Info Cards -->
<section class="container my-5 text-center" id="sales">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <i class='bx bxs-map-pin fs-1 text-primary'></i>
                    <h4 class="fw-bold">Stay</h4>
                    <p>Stay in comfort, stay in style. Your perfect getaway starts right here, without going far.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <i class='bx bx-wink-smile fs-1 text-success'></i>
                    <h4 class="fw-bold">Enjoy</h4>
                    <p>Enjoy cozy spaces, fresh linens, and the kind of peace that makes you want to stay just a little longer.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <i class='bx bxs-user-check fs-1 text-warning'></i>
                    <h4 class="fw-bold">Relax</h4>
                    <p>Relax and recharge in a space designed for rest, where every detail whispers calm and comfort.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Properties -->
<section class="container my-5" id="properties">
    <div class="text-center mb-5">
        <span class="text-primary">Recent</span>
        <h2 class="fw-bold">Our Featured Houses</h2>
        <p class="text-muted">Our houses are warm, quiet, and thoughtfully minimal. With clean design and all the essentials, they offer the perfect setting for restful, easygoing stays.</p>
    </div>
    <div class="row g-4">
        @foreach($staycations as $staycation)
            @if($staycation->house_availability === 'available')
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <img src="{{ asset('storage/' . $staycation->house_image) }}" 
                             class="card-img-top" 
                             alt="{{ $staycation->house_name }}">
                        <div class="card-body">
                            <h5 class="card-title">{{ $staycation->house_name }}</h5>
                            <p class="text-muted mb-1">{{ $staycation->house_location }}</p>
                            <p>{{ $staycation->house_description }}</p>
                            <h6 class="fw-bold mt-2">{{ number_format($staycation->house_price, 2) }} PHP</h6>
                            <a href="{{ url('booking', $staycation->id) }}" class="btn btn-primary w-100 mt-2">Book Now</a>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</section>

<!-- Testimonials -->
<section class="container my-5" id="testimonials">
    <div class="text-center mb-5">
        <h2 class="fw-bold">What Our Clients Say</h2>
    </div>
    <div class="row g-4">
        <div class="col-md-3">
            <div class="card h-100 shadow-sm text-center p-3">
                <img src="../assets/Matt.png" class="rounded-circle mx-auto mb-3" width="80" alt="">
                <h5 class="fw-bold">Darwin</h5>
                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit...</p>
                <div class="text-warning">
                    <i class='bx bxs-star'></i><i class='bx bxs-star'></i>
                    <i class='bx bxs-star'></i><i class='bx bxs-star'></i>
                    <i class='bx bxs-star'></i>
                </div>
            </div>
        </div>
        <!-- Repeat for other testimonials -->
    </div>
</section>

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

@endsection
@section('Footer')
    @include('Footer')
@endsection

