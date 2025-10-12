<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Sidebar</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    /* Sidebar Styling */
    .offcanvas-start {
      width: 250px;
      background-color: #1a1a1a;
      color: #fff;
    }

    .offcanvas .nav-link {
      color: #ccc;
      padding: 0.8rem 1rem;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 0.95rem;
      transition: 0.2s;
    }

    .offcanvas .nav-link:hover,
    .offcanvas .nav-link.active {
      background-color: #333;
      color: #fff;
    }

    .offcanvas .logo {
      font-size: 1.5rem;
      font-weight: bold;
      padding: 1rem;
      background: #111;
      text-align: center;
      color: #fff;
      letter-spacing: 1px;
    }

    .sidebar-toggle {
      position: fixed;
      top: 1rem;
      left: 1rem;
      z-index: 1050;
    }

    @media (min-width: 992px) {
      .offcanvas-lg {
        position: static;
        transform: none !important;
        visibility: visible !important;
        background-color: #1a1a1a;
        width: 250px;
        border-right: 1px solid #333;
      }

      .sidebar-toggle {
        display: none;
      }
    }

    .nav-link.text-danger {
      color: #ff5a5a !important;
    }

    .nav-link.text-danger:hover {
      background-color: #661d1d;
      color: #fff !important;
    }
  </style>
</head>
<body>
  <!-- ✅ Sidebar Toggle Button (Mobile Only) -->
  <button class="btn btn-dark sidebar-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar">
    <i class="fa-solid fa-bars"></i>
  </button>

  <!-- ✅ Sidebar -->
  <div class="offcanvas offcanvas-start offcanvas-lg" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel">
    <div class="offcanvas-header d-lg-none">
      <h5 class="offcanvas-title" id="sidebarLabel">Menu</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body p-0">
      <div class="logo">STAYCATION</div>
      <nav class="nav flex-column">
        <a href="{{ route('admin.dashboard') }}" 
           class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
           <i class="fa-solid fa-gauge"></i> Dashboard
        </a>

        <a href="{{ route('admin.customers') }}" 
           class="nav-link {{ request()->routeIs('admin.customers') ? 'active' : '' }}">
           <i class="fa-solid fa-user"></i> Customer
        </a>

        <a href="{{ route('admin.analytics') }}" 
           class="nav-link {{ request()->routeIs('admin.analytics') ? 'active' : '' }}">
           <i class="fa-solid fa-chart-line"></i> Analytics
        </a>

        <a href="{{ route('admin.messages') }}" 
           class="nav-link {{ request()->routeIs('admin.messages') ? 'active' : '' }}">
           <i class="fa-solid fa-envelope"></i> Messages
        </a>

        <a href="{{ route('admin.bookings') }}" 
           class="nav-link {{ request()->routeIs('admin.bookings') ? 'active' : '' }}">
           <i class="fa-solid fa-calendar-check"></i> Bookings
        </a>

        <a href="{{ route('admin.reports') }}" 
           class="nav-link {{ request()->routeIs('admin.reports') ? 'active' : '' }}">
           <i class="fa-solid fa-file-alt"></i> Reports
        </a>

        <a href="{{ route('admin.settings') }}" 
           class="nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
           <i class="fa-solid fa-cog"></i> Settings
        </a>

        <a href="{{ route('admin.addproduct') }}" 
           class="nav-link {{ request()->routeIs('admin.addproduct') ? 'active' : '' }}">
           <i class="fa-solid fa-plus"></i> Add Product
        </a>

        <!-- Logout -->
        <a href="#" class="nav-link text-danger" id="logout-link">
          <i class="fa-solid fa-right-from-bracket"></i> Log out
        </a>
      </nav>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // ✅ Logout function
    const logoutLink = document.getElementById('logout-link');
    if (logoutLink) {
      logoutLink.addEventListener('click', function (e) {
        e.preventDefault();
        fetch("{{ route('logout') }}", {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
          },
        }).then(() => {
          window.location.href = "{{ route('home') }}";
        });
      });
    }
  </script>
</body>
</html>
