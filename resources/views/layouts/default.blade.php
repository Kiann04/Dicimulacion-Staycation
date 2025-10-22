<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dicimulacion Staycation')</title>

    {{-- Styles --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">

    @stack('styles')
</head>
<body>
    <header>
        @yield('Header')
    </header>

    @if(View::hasSection('Aside'))
        <aside>
            @yield('Aside')
        </aside>
    @endif

    <main>
        @yield('content')
    </main>

    <footer>
        @yield('Footer')
    </footer>

    <section id="analytics">
        @yield('Analytics')
    </section>

    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>

    @stack('scripts')
</body>
</html>
