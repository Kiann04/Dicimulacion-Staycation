<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dicimulacion Staycation</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">

    <style>
        /* Navbar Custom Styling */
        .navbar {
            background: rgba(0, 0, 0, 0.5); /* Semi-transparent */
            backdrop-filter: blur(10px);
            transition: 0.3s ease;
        }
        .navbar .nav-link {
            color: #fff !important;
            font-weight: 500;
            transition: color 0.2s ease;
        }
        .navbar .nav-link:hover {
            color: #ffd700 !important;
        }
        .navbar .navbar-brand {
            color: #fff !important;
            font-size: 1.6rem;
        }
        .navbar .btn-primary {
            background-color: #ffd700;
            border-color: #ffd700;
            color: #000;
            font-weight: 500;
            transition: 0.3s;
        }
        .navbar .btn-primary:hover {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #000;
        }
        /* Navbar shadow on scroll */
        .scrolled {
            background: rgba(0, 0, 0, 0.85) !important;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <!-- Brand / Logo -->
            <a class="navbar-brand d-flex align-items-center" href="{{ route('home') }}">
                <!-- Icon -->
                <i class='bx bx-home-alt me-2' style="font-size: 1.8rem; color: #ffd700;"></i>
                <!-- Gradient Text Logo -->
                <span style="
                    font-size: 1.6rem;
                    font-weight: 700;
                    background: linear-gradient(45deg, #ffb347, #ffcc33);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    ">
                    Dicimulacion
                </span>
            </a>

            <!-- Mobile Toggler -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" 
                    aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon text-white"></span>
            </button>

            <!-- Navbar Items -->
            <div class="collapse navbar-collapse" id="navbarNavDropdown">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item"><a class="nav-link" href="{{ route('home') }}">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('home') }}#about">About Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('home') }}#properties">Houses</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('home') }}#contact">Contact Us</a></li>

                    @guest
                        @if (!Route::is('login') && !Route::is('register'))
                            <li class="nav-item">
                                <a class="btn btn-primary ms-lg-3" href="{{ route('login') }}">Log in</a>
                            </li>
                        @endif
                    @endguest

                    @auth
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle btn btn-outline-light ms-lg-3" href="#" id="userDropdown" 
                               role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                {{ Auth::user()->name }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="{{ route('profile.show') }}">Profile</a></li>

                                @if(Auth::user()->usertype === 'admin')
                                    <li><a class="dropdown-item" href="{{ url('/admin/dashboard') }}">Dashboard</a></li>
                                @elseif(Auth::user()->usertype === 'staff')
                                    <li><a class="dropdown-item" href="{{ url('/staff/dashboard') }}">Dashboard</a></li>
                                @endif

                                @if(!in_array(Auth::user()->usertype, ['admin', 'staff']))
                                    <li><a class="dropdown-item" href="{{ route('BookingHistory.index') }}">Booking History</a></li>
                                @endif

                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger">Logout</button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <!-- Optional: Add a full-screen hero image -->
    <section class="hero vh-100 d-flex justify-content-center align-items-center" style="background: url('{{ asset('Assets/View1.jpg') }}') center/cover no-repeat;">
        <div class="text-center text-white">
            <h1 class="display-4 fw-bold">Relax. Stay. Enjoy.</h1>
            <p class="lead">Find your perfect place at Dicimulacion Staycation</p>
            <a href="{{ url('register') }}" class="btn btn-primary btn-lg mt-3">Sign Up</a>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if(window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>
