<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Tailwind -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.3/dist/tailwind.min.css" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="font-sans antialiased">
    <x-banner />

    <div class="min-h-screen bg-gray-100">
        @livewire('navigation-menu')

        <!-- Page Heading -->
        @if (isset($header))
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>

    @stack('modals')

    @livewireScripts

    <!-- Consent Popup -->
    @if(!Cookie::get('user_consent'))
    <div x-data="{ open: true }" x-show="open" x-transition:enter="transition transform ease-out duration-500"
         x-transition:enter-start="translate-y-full opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transition transform ease-in duration-300"
         x-transition:leave-start="translate-y-0 opacity-100"
         x-transition:leave-end="translate-y-full opacity-0"
         x-cloak
         class="fixed bottom-4 left-1/2 transform -translate-x-1/2 bg-white border shadow-lg rounded-xl p-6 max-w-md w-full z-50">
        <h5 class="text-lg font-semibold mb-2">We Use Cookies</h5>
        <p class="text-gray-600 mb-4 text-sm">
            Essential cookies are required for site operation. Optional cookies improve your experience.
        </p>

        <div class="flex flex-col sm:flex-row justify-center gap-3">
            <button @click="acceptAll()" class="bg-orange-500 text-white px-4 py-2 rounded hover:bg-orange-600 transition w-full sm:w-auto">
                Accept All
            </button>
            <button @click="rejectAll()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 transition w-full sm:w-auto">
                Reject All
            </button>
        </div>
    </div>
    @endif

    <!-- Consent Script -->
    <script>
        function setConsent(choice) {
            fetch("{{ route('save.consent') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({ choice })
            }).then(() => {
                // Hide the popup smoothly
                const popup = document.querySelector('[x-data]');
                popup.__x.$data.open = false;

                // Load optional tracking if accepted
                if(choice === 'accept') {
                    const script = document.createElement('script');
                    script.src = "https://www.googletagmanager.com/analytics.js";
                    document.body.appendChild(script);
                }
            });
        }

        function acceptAll() { setConsent('accept'); }
        function rejectAll() { setConsent('reject'); }

        // Load tracking if already accepted
        document.addEventListener('DOMContentLoaded', () => {
            const consent = "{{ Cookie::get('user_consent') }}";
            if(consent === 'accept') {
                const script = document.createElement('script');
                script.src = "https://www.googletagmanager.com/analytics.js";
                document.body.appendChild(script);
            }
        });
    </script>
</body>
</html>
