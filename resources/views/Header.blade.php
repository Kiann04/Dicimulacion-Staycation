<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../Css/Homepage.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/light/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/bold/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet"/>
    
    <title>Dicimulacion Staycation</title>
</head>
<body>

    <!--NavBar-->
    <header>
        <div class="nav container">
            <a href="{{ route('home') }}" class="logo"><i class='bx bx-home-alt'></i> Dicimulacion</a>

            <!--Menu Icon-->
            <input type="checkbox" name="" id="menu">
            <label for= "menu"><i class='bx bx-menu' id="menu-icon"></i></label>
            <!--Nav List-->
            <ul class="navbar">
                <li><a href="{{ route('home') }}">Home</a></li>
                <li><a href="{{ route('home') }}#about">About us</a></li>
                <li><a href="{{ route('home') }}#properties">Houses</a></li>
                <li><a href="{{ route('home') }}#contact">Contact us</a></li>       
            </ul>
            </ul>
            <!-- Authentication -->
        @guest
            <li class="mobile-signin">
                <a href="{{ route('login') }}" class="btn">Sign In</a>
            </li>
            <a href="{{ route('register') }}" class="btn">Sign Up</a>
        @endguest

        @auth
            <li class="dropdown">
                <a href="#" class="btn">
                    {{ Auth::user()->name }} <i class="bx bx-chevron-down"></i>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="{{ route('profile.show') }}">Profile</a></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="logout-btn">Logout</button>
                        </form>
                    </li>
                </ul>
            </li>
        @endauth
    </div>
</header>