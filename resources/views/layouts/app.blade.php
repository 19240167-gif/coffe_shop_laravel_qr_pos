<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Coffee POS QR')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="ambient-orb ambient-orb-left"></div>
    <div class="ambient-orb ambient-orb-right"></div>

    <header class="site-header">
        <div class="shell nav-wrap">
            <a href="{{ auth()->check() ? route('dashboard.index') : route('login') }}" class="brand">
                <span class="brand-mark">BeanFlow</span>
                <span class="brand-sub">Laravel QR POS</span>
            </a>
            <nav class="nav-links">
                <span class="live-pulse">Live Service</span>
                @auth
                    <a href="{{ route('dashboard.index') }}">Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="nav-link-btn">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}">Login Staf</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="shell main-content">
        @if (session('success'))
            <div class="notice notice-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="notice notice-error">
                <strong>Perlu diperbaiki:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <div id="toast-stack" aria-live="polite" aria-atomic="true"></div>

    @stack('scripts')
</body>
</html>
