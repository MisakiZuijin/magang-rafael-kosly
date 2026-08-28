<!DOCTYPE html>
<html lang="id" class="scrollbar-hide">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Kosly')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

    {{-- DARK MODE: Cek localStorage sebelum render apapun --}}
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            if (theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }

        * {
            -webkit-tap-highlight-color: transparent;
            -ms-overflow-style: none !important;
            scrollbar-width: none !important;
        }

        *::-webkit-scrollbar,
        html::-webkit-scrollbar,
        body::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
            background: transparent !important;
        }

        input[type="text"]:focus, input[type="number"]:focus, input[type="email"]:focus, input[type="password"]:focus, input[type="date"]:focus, input[type="url"]:focus, input[type="file"]:focus, select:focus, textarea:focus {
            outline: none !important;
            border-color: #10b981 !important;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.25) !important;
        }

        button:focus, button:focus-visible, a:focus, a:focus-visible, input[type="radio"]:focus, input[type="checkbox"]:focus, .navbar-avatar:focus, .modal-close-btn:focus {
            outline: none !important;
            box-shadow: none !important;
        }
    </style>
</head>

<body class="bg-gray-200 dark:bg-gray-950">

    <div id="app-container"
        x-data="{ sidebarOpen: false }"
        class="relative w-full max-w-[430px] mx-auto h-screen flex flex-col bg-gray-50 dark:bg-gray-950 shadow-2xl overflow-hidden isolate">

        @include('components.navbar')

        <div x-show="sidebarOpen"
            x-cloak
            x-transition.opacity.duration.300ms
            @click="sidebarOpen = false"
            class="absolute inset-0 bg-black/60 backdrop-blur-sm z-40"></div>

        @include('components.sidebar')

        <main class="flex-1 mt-16 mb-12 overflow-y-auto no-scrollbar">
            <div class="p-4 pb-6">
                @if(session('success'))
                @include('components.toast', ['type' => 'success', 'message' => session('success')])
                @endif
                @if(session('error'))
                @include('components.toast', ['type' => 'error', 'message' => session('error')])
                @endif

                @yield('content')
            </div>
        </main>

        @include('components.floating-wa-group')
        @include('components.footer')

    </div>

    @stack('scripts')
</body>

</html>