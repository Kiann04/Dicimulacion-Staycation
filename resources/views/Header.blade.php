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
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm fixed-top">
        <div class="container">
            <!-- Brand / Logo -->
            <a class="navbar-brand fw-bold d-flex align-items-center" href="{{ route('home') }}">
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
<script>
document.addEventListener("DOMContentLoaded", function() {
    const collapseEl = document.querySelector('.navbar-collapse');
    const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl);

    // Close when a link inside the menu is clicked
    document.querySelectorAll('.navbar-collapse .nav-link').forEach(link => {
        link.addEventListener('click', () => {
            if (collapseEl.classList.contains('show')) {
                bsCollapse.hide();
            }
        });
    });

    // Close when clicking outside the navbar (mobile only)
    document.addEventListener('click', function(event) {
        const isClickInside = collapseEl.contains(event.target) || 
                              document.querySelector('.navbar-toggler').contains(event.target);
        if (!isClickInside && collapseEl.classList.contains('show')) {
            bsCollapse.hide();
        }
    });
});
</script>


</body>
</html>
