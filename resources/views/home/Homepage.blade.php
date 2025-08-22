@extends('layouts.default')

@section('Header')
    @include('Header')
@endsection


    <!--Home-->
    <section class="home container" id="home">
        <div class="home-text">
            <h1>Find Your Next<br>Perfect Place To<br>Relax</h1>
            <a href="{{ url('signup') }}" class="btn">Sign Up</a>
        </div>
    </section>

    <!--About-->
    <section class="about container" id="about">
        <div class="about-img">
            <img src="{{ asset('Assets/AboutUs.jpg') }}" alt="">
        </div>
        <div class="about-text">
            <span>About Us</span>
            <h2>We Provide The Best<br>Place For You</h2>
            <p>We’re a family-owned staycation business with eight homes, all located with beautiful city lights or surrounded by peaceful nature.</p>
            <p>Whether you want to enjoy the city lights or relax in the calm of nature, we’ve created spaces where you can slow down and feel right at home.</p>
            <p>Each of our homes is carefully designed and cared for by our family to make sure your stay is comfortable and memorable.</p>
            <p>We believe in treating guests like friends, with thoughtful details and a personal touch that make all the difference. Come stay with us—and be a part of our story.</p>
            <a href="#" class="btn">Learn More</a>
        </div>
    </section>

    <!--Info-->
    <section class="sales container" id="sales">
        <div class="box">
            <i class='bx bxs-map-pin'></i>
            <h3>Stay</h3>
            <p>Stay in comfort, stay in style. Your perfect getaway starts right here, without going far.</p>
        </div>

        <div class="box">
            <i class='bx bx-wink-smile'></i>
            <h3>Enjoy</h3>
            <p>Enjoy cozy spaces, fresh linens, and the kind of peace that makes you want to stay just a little longer.</p>
        </div>

        <div class="box">
            <i class='bx bxs-user-check'></i>
            <h3>Relax</h3>
            <p>Relax and recharge in a space designed for rest, where every detail whispers calm and comfort.</p>
        </div>
    </section>

    <!--Properties-->
    <section class="properties container" id="properties">
        <div class="heading">
            <span>Recent</span>
                <h2>Our Featured Houses</h2>
                    <p>Our houses are warm, quiet, and thoughtfully minimal. With clean design and all the essentials, they offer the perfect setting for restful, easygoing stays.</p>
        </div>
        <div class="properties-container container">
                @foreach($staycations as $staycation)
                @if($staycation->house_availability === 'available')
            <div class="box">
                    <a href="{{ url('Booking', $staycation->id) }}">
                        @csrf
                        <img src="{{ asset('storage/' . $staycation->house_image) }}" 
                            alt="{{ $staycation->house_name }}" class="house-image" />
                                <h3>{{ number_format($staycation->house_price, 2) }}php</h3>
                                <div class="content">
                                <div class="text">
                                <h3>{{ $staycation->house_name }}</h3>
                                <p>{{ $staycation->house_location }}</p>
                            </div>
                                <div class="icon">
                            </div>
                        </div>
                    </a>        
                </div>
            @endif
        @endforeach
    </div>
</section>

    <!--Testimonials-->
    <section class="testimonials">
        <div class="container">
            <div class="section-header">
                <h2 class="title">What Our Clients Say</h2>
            </div>
            <div class="testimonials-content">
                <div class="swiper testimonials-slider js-testimonials-slider">
                    <div class="swiper-wrapper">
                        <!-- Slide 1 -->
                        <div class="swiper-slide testimonials-item">
                            <div class="info">
                                <img src="{{ asset('Assets/Matt.png') }}" alt="">
                                <div class="text-box">
                                    <h3 class="name">Darwin</h3>
                                </div>
                            </div>
                            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit...</p>
                            <div class="rating">
                                <i class='bx bxs-star'></i><i class='bx bxs-star'></i>
                                <i class='bx bxs-star'></i><i class='bx bxs-star'></i>
                                <i class='bx bxs-star'></i>
                            </div>
                        </div>
                        <!-- Slide 2 -->
                        <div class="swiper-slide testimonials-item">
                            <div class="info">
                                <img src="{{ asset('Assets/test.jpg') }}" alt="">
                                <div class="text-box">
                                    <h3 class="name">Sophia</h3>
                                </div>
                            </div>
                            <p>Excellent service and support. Highly recommend!</p>
                            <div class="rating">
                                <i class='bx bxs-star'></i><i class='bx bxs-star'></i>
                                <i class='bx bxs-star'></i><i class='bx bxs-star'></i>
                                <i class='bx bxs-star'></i>
                            </div>
                        </div>
                        <!-- Slide 3 -->
                        <div class="swiper-slide testimonials-item">
                            <div class="info">
                                <img src="{{ asset('Assets/Matt.png') }}" alt="">
                                <div class="text-box">
                                    <h3 class="name">Liam</h3>
                                </div>
                            </div>
                            <p>They delivered more than I expected. Fantastic job!</p>
                            <div class="rating">
                                <i class='bx bxs-star'></i><i class='bx bxs-star'></i>
                                <i class='bx bxs-star'></i><i class='bx bxs-star'></i>
                                <i class='bx bxs-star-half'></i>
                            </div>
                        </div>
                        <!-- Slide 4 -->
                        <div class="swiper-slide testimonials-item">
                            <div class="info">
                                <img src="{{ asset('Assets/test.jpg') }}" alt="">
                                <div class="text-box">
                                    <h3 class="name">Ava</h3>
                                </div>
                            </div>
                            <p>Truly professional and creative. Will work again!</p>
                            <div class="rating">
                                <i class='bx bxs-star'></i><i class='bx bxs-star'></i>
                                <i class='bx bxs-star'></i><i class='bx bxs-star'></i>
                                <i class='bx bxs-star'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!--Contact Us-->
    <section class="newsletter container" id="contact">
    <h2>Have a Question? <br> Contact Us</h2>
    <form action="{{ route('contact.send') }}" method="POST">
        @csrf
        
        <!-- Email Input -->
        <input 
            type="email" 
            name="email" 
            placeholder="Your Email" 
            required
        >

        <!-- Message Box -->
        <textarea 
            name="message" 
            placeholder="Write your message here..." 
            required
        ></textarea>

        <!-- Submit Button -->
        <input type="submit" value="Send" class="btn">
    </form>
</section>

    <!--Chatbot-->
    <div class="CHT">
        <button id="ChatbotBtn" class="FloatingChatbot">
            <i class="ph-bold ph-chat-circle-text"></i>
        </button>

        <div id="ChatbotContainer" class="ChatbotContainerHidden hidden">
            <div class="chatbot-header">
                <span>FAQs</span>
                <button id="CloseChatBot">&times;</button>
            </div>
            <div class="ChatbotContent">
                <!-- FAQ Items here (use the same structure as before, with asset URLs if needed) -->
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        const swiper = new Swiper('.js-testimonials-slider', {
            loop: true,
            slidesPerView: 1,
            spaceBetween: 30,
            autoplay: { delay: 3000, disableOnInteraction: false },
            pagination: { el: '.swiper-pagination', clickable: true },
            navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
        });
    </script>
    <script src="{{ asset('Javascript/ChatBot.js') }}"></script>


@section('Footer')
    @include('Footer')
@endsection
