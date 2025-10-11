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

{{-- Optional JS: collapse menu on mobile when clicking a link --}}
<script>
document.addEventListener("DOMContentLoaded", function() {
    const collapseEl = document.querySelector('.navbar-collapse');
    if (!collapseEl) return;

    collapseEl.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', () => {
            // Only collapse if visible (mobile)
            if (window.getComputedStyle(collapseEl).display !== 'none') {
                const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl);
                bsCollapse.hide();
            }
        });
    });
});
</script>
