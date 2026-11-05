<!doctype html>
<html lang="id">
<head>
    @include('partials.head')
    @stack('head')
    <title>@yield('title', 'OCEAN 2026') · OCEAN 2026</title>
</head>
<body class="public">
@unless(View::hasSection('plain'))
    @include('partials.deco')
@endunless
@yield('before')
<div class="wrap">
    @unless(View::hasSection('hide-logo') || View::hasSection('plain'))
        <img class="logo" src="{{ asset('image/ocean-logo.png') }}" alt="OCEAN 2026: Olympiad of Competency in English, Agro, and Marine">
    @endunless
    @include('partials.flash')
    @include('partials.loading')
    @yield('content')
    <footer class="page-foot">
    <div class="brand-foot">
        <img src="{{ asset('image/ocean-upa.png') }}" alt="UPA Bahasa Universitas Teuku Umar">
    </div>
    <p class="footer-note" style="margin-top:6px">OCEAN 2026: Olympiad of Competency in English, Agro, and Marine</p>
    </footer>
    {{-- spacer so the waves never cover the footer --}}
    @unless(View::hasSection('plain'))<div style="height:70px"></div>@endunless
</div>
@stack('scripts')
</body>
</html>
