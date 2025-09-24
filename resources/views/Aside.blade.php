<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ADMIN</title>
  
  <!-- CSS -->
  <link rel="stylesheet" href="{{ asset('css/Admin.css') }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <!-- ✅ Sidebar Toggle -->
  <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>

  <!-- ✅ Sidebar -->
  <aside class="sidebar">
    <button class="close-btn" onclick="closeSidebar()">✖</button>
    <div class="logo">STAYCATION</div>
    <nav class="menu">
      <a href="{{ route('admin.dashboard') }}"><i class="fa-solid fa-gauge"></i> Dashboard</a>
      <a href="{{ route('admin.customers') }}"><i class="fa-solid fa-user"></i> Customer</a>
      <a href="{{ route('admin.analytics') }}"><i class="fa-solid fa-chart-line"></i> Analytics</a>
      <a href="{{ route('admin.messages') }}"><i class="fa-solid fa-envelope"></i> Messages</a>
      <a href="{{ route('admin.bookings') }}"><i class="fa-solid fa-calendar-check"></i> Bookings</a>
      <a href="{{ route('admin.reports') }}"><i class="fa-solid fa-file-alt"></i> Reports</a>
      <a href="{{ route('admin.settings') }}"><i class="fa-solid fa-cog"></i> Settings</a>
      <a href="{{ route('admin.addproduct') }}"><i class="fa-solid fa-plus"></i> Add Product</a>
      <!-- Logout -->
      <a href="#" id="logout-link"><i class="fa-solid fa-right-from-bracket"></i> Log out</a>
    </nav>
  </aside>

  <!-- ✅ JS -->
  <script>
    // Toggle sidebar and hide burger
    function toggleSidebar() {
      const sidebar = document.querySelector('.sidebar');
      const toggleBtn = document.querySelector('.sidebar-toggle');
      sidebar.classList.add('active');
      toggleBtn.style.display = "none"; // hide burger when sidebar opens
    }

    // Close sidebar and show burger again
    function closeSidebar() {
      const sidebar = document.querySelector('.sidebar');
      const toggleBtn = document.querySelector('.sidebar-toggle');
      sidebar.classList.remove('active');
      toggleBtn.style.display = "block"; // show burger back
    }

    // ✅ Logout
    const logoutLink = document.getElementById('logout-link');
    if (logoutLink) {
      logoutLink.addEventListener('click', function(e) {
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
