<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dicimulacion Staycation</title>

    {{-- Bootstrap CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Boxicons --}}
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">


    <style>
        /* Ensure the footer sticks to the bottom */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        main {
            flex: 1; /* grow to fill available space */
        }
        footer {
            background: #f8f9fa;
        }

        /* ✅ Fix: Prevent right white space from navbar collapse */
        html, body {
            overflow-x: hidden !important;
        }

        .navbar,
        .navbar-collapse {
            max-width: 100vw !important;
            overflow-x: visible !important;
        }
    </style>

    @stack('styles')
</head>
<body>
    <!-- Header -->
    <header>
        @yield('Header')
    </header>

    <!-- Sidebar (optional) -->
    <aside>
        @yield('Aside')
    </aside>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer>
        @yield('Footer')
    </footer>

    <analytics>
        @yield('Analytics')
    </analytics>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    {{-- Mobile Navbar Toggle Script --}}
    @stack('scripts')
</body>
</html>
