<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>STAFF</title>
  
  <!-- CSS -->
  <link rel="stylesheet" type="text/css" href="{{ asset('Css/Admin.css') }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <!-- ✅ Sidebar Toggle -->
  <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>
<aside class="sidebar">
    <button class="close-btn" onclick="closeSidebar()">✖</button>
    <div class="logo">STAYCATION</div>
    <nav class="menu">
        <a href="{{ route('staff.dashboard') }}"><i class="fa-solid fa-gauge"></i> Dashboard</a>
        <a href="{{ route('staff.customers') }}"><i class="fa-solid fa-user"></i> Customers</a>
        <a href="{{ route('staff.bookings') }}"><i class="fa-solid fa-calendar-check"></i> Bookings</a>
        <a href="{{ route('staff.messages') }}"><i class="fa-solid fa-envelope"></i> Messages</a>
        <a href="#" id="logout-link"><i class="fa-solid fa-right-from-bracket"></i> Log out</a>
    </nav>
</aside>

<script>
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const toggleBtn = document.querySelector('.sidebar-toggle');
        sidebar.classList.add('active');
        toggleBtn.style.display = "none";
    }

    function closeSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const toggleBtn = document.querySelector('.sidebar-toggle');
        sidebar.classList.remove('active');
        toggleBtn.style.display = "block";
    }

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