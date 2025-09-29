
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DIcimulacion Staycation</title>
</head>
<body>
    <header>
        @yield ('Header')
</header>
    <aside>
        @yield ('Aside')
    </aside>
    <footer>
        @yield ('Footer')
    </footer>
    <head>
    @stack('styles')
</head>
</body>
</html>