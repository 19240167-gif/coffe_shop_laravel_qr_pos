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
            <a href="{{ route('dashboard.index') }}" class="brand">
                <span class="brand-mark">BeanFlow</span>
                <span class="brand-sub">Laravel QR POS</span>
            </a>
            <nav class="nav-links">
                <a href="{{ route('dashboard.index') }}">Dashboard</a>
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

    @stack('scripts')
</body>
</html>
