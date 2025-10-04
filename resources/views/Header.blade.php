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
        /* Glass effect navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.75) !important;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            transition: all 0.3s ease-in-out;
        }

        /* Brand logo */
        .navbar-brand {
            font-weight: 700;
            font-size: 1.3rem;
            color: #0d6efd !important;
        }

        /* Links */
        .navbar-nav .nav-link {
            font-weight: 500;
            position: relative;
            padding: 8px 15px;
            transition: color 0.3s ease;
        }
        .navbar-nav .nav-link:hover {
            color: #0d6efd !important;
        }

        /* Underline animation */
        .navbar-nav .nav-link::after {
            content: "";
            position: absolute;
            width: 0;
            height: 2px;
            background: #0d6efd;
            left: 0;
            bottom: 0;
            transition: width 0.3s;
        }
        .navbar-nav .nav-link:hover::after {
            width: 100%;
        }

        /* Buttons */
        .btn-primary {
            border-radius: 30px;
            padding: 8px 20px;
        }
        .btn-outline-primary {
            border-radius: 30px;
            padding: 8px 20px;
        }

        /* Dropdown */
        .dropdown-menu {
            border-radius: 12px;
            box-shadow: 0px 4px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top shadow-sm">
        <div class="container">
            <!-- Brand / Logo -->
            <a class="navbar-brand d-flex align-items-center" href="{{ route('home') }}">
                <i class='bx bx-home-alt me-2'></i> Dicimulacion
            </a>

            <!-- Mobile Toggler -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" 
                    aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navbar Items -->
            <div class="collapse navbar-collapse" id="navbarNavDropdown">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('home') }}">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('home') }}#about">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('home') }}#properties">Houses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('home') }}#contact">Contact Us</a>
                    </li>

                    @guest
                        @if (!Route::is('login') && !Route::is('register'))
                            <li class="nav-item">
                                <a class="btn btn-primary ms-lg-3" href="{{ route('login') }}">Log in</a>
                            </li>
                        @endif
                    @endguest

                    @auth
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle btn btn-outline-primary ms-lg-3" href="#" id="userDropdown" 
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
