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

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    {{-- Mobile Navbar Toggle Script --}}
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const collapseEl = document.querySelector('.navbar-collapse');
        if (!collapseEl) return;

        const toggler = document.querySelector('.navbar-toggler');
        const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl);

        // Close menu when a link is clicked
        collapseEl.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (collapseEl.classList.contains('show')) {
                    bsCollapse.hide();
                }
            });
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const isClickInside = collapseEl.contains(event.target) || toggler.contains(event.target);
            if (!isClickInside && collapseEl.classList.contains('show')) {
                bsCollapse.hide();
            }
        });
    });
    </script>

    @stack('scripts')
</body>
</html>
