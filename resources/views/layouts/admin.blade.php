<!doctype html>
<html lang="id">
<head>
    @include('partials.head')
    @stack('head')
    <title>@yield('title', 'Admin') · OCEAN 2026</title>
</head>
<body>
@php
    $menu = [
        ['admin.dashboard', 'admin.dashboard', 'Dashboard', 'M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z'],
        ['admin.lobby', 'admin.lobby', 'Lobby (Proyektor)', 'M21 3H3a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h7v2H8v2h8v-2h-2v-2h7a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zm0 13H3V5h18v11z'],
        ['admin.participants.index', 'admin.participants.*', 'Peserta', 'M16 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm-8 0a3 3 0 1 0-3-3 3 3 0 0 0 3 3zm0 2c-2.7 0-8 1.3-8 4v3h8v-3c0-1 .4-2 1.3-2.8A11 11 0 0 0 8 13zm8 0c-2.7 0-8 1.3-8 4v3h16v-3c0-2.7-5.3-4-8-4z'],
        ['admin.questions.index', ['admin.questions.*', 'admin.passages.*'], 'Bank Soal', 'M19 2H5a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm8 8h-6v-2h6v2zm0-4h-6v-2h6v2zm0-4h-6V7h6v2z'],
        ['admin.settings', 'admin.settings', 'Pengaturan', 'M19.4 13a7.5 7.5 0 0 0 0-2l2.1-1.6-2-3.5-2.5 1a7.6 7.6 0 0 0-1.7-1L15 3h-4l-.4 2.9a7.6 7.6 0 0 0-1.7 1l-2.5-1-2 3.5L6.6 11a7.5 7.5 0 0 0 0 2l-2.1 1.6 2 3.5 2.5-1a7.6 7.6 0 0 0 1.7 1L11 21h4l.4-2.9a7.6 7.6 0 0 0 1.7-1l2.5 1 2-3.5zM13 15.5a3.5 3.5 0 1 1 3.5-3.5 3.5 3.5 0 0 1-3.5 3.5z'],
    ];
@endphp
<div class="admin" id="admin">
    <aside class="sidebar">
        <a href="{{ route('admin.dashboard') }}" class="brand"><img src="{{ asset('image/ocean-wordmark.png') }}" alt="OCEAN 2026"></a>
        <div class="brand-name">Olympiad of Competency in<br>English, Agro, and Marine</div>
        <svg class="side-wave" viewBox="0 0 260 120" preserveAspectRatio="none" aria-hidden="true">
            <path d="M0 50c45-20 85-20 130 0s85 20 130 0v70H0z" fill="rgba(255,255,255,.05)"/>
            <path d="M0 75c45-16 85-16 130 0s85 16 130 0v45H0z" fill="rgba(255,255,255,.07)"/>
        </svg>
        <div class="section-label">Menu</div>
        <nav>
            @foreach($menu as [$route, $pattern, $label, $icon])
                <a href="{{ route($route) }}" class="{{ request()->routeIs($pattern) ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="{{ $icon }}"/></svg>{{ $label }}
                </a>
            @endforeach
        </nav>
        <div class="foot">
            Masuk sebagai <strong>{{ auth()->user()->name }}</strong>
            <form method="post" action="{{ route('logout') }}">@csrf
                <button class="btn-link"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M10 17l1.4-1.4L8.8 13H20v-2H8.8l2.6-2.6L10 7l-5 5 5 5zM4 5h8V3H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h8v-2H4V5z"/></svg>Logout</button>
            </form>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <button class="menu-btn" aria-label="Buka menu" onclick="document.getElementById('admin').classList.toggle('nav-open')">
                <svg viewBox="0 0 24 24" width="26" height="26" fill="currentColor"><path d="M3 6h18v2H3zm0 5h18v2H3zm0 5h18v2H3z"/></svg>
            </button>
            <h1>@yield('title', 'Admin')</h1>
            <span class="spacer"></span>
            @yield('actions')
        </header>
        <main class="content">
            @include('partials.flash')
            @include('partials.loading')
            @yield('content')
        </main>
    </div>
</div>
<script>
    // Close the mobile drawer when tapping the dimmed backdrop.
    document.getElementById('admin').addEventListener('click', e => {
        if (e.target.id === 'admin') e.currentTarget.classList.remove('nav-open');
    });
</script>
@stack('scripts')
</body>
</html>
