@extends('layouts.app')

@section('title', 'Masuk')
@section('hide-logo', true)

@push('head')<style>.public .wrap { max-width: 1020px; }</style>@endpush

@section('content')
<div class="auth">
    <div class="auth-hero">
        <img class="logo-sm" src="{{ asset('image/ocean-wordmark.png') }}" alt="OCEAN 2026">
        <h1>Olympiad of Competency in English, Agro, and Marine</h1>
        <p>Tunjukkan kemampuan bahasa Inggrismu dan raih juara!</p>
        <div class="tagline"><span>Structure</span><span>Reading Comprehension</span></div>
        <img class="chars" src="{{ asset('image/ocean-characters.png') }}" alt="">
    </div>
    <div class="auth-form">
        <h2>Selamat datang! 👋</h2>
        <p class="muted" style="margin-top:0">Masuk dengan akun yang diberikan panitia.</p>
        <form method="post" action="{{ url('/login') }}">
            @csrf
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="{{ old('username') }}" required autofocus autocomplete="username">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
            <button class="btn btn-lg btn-block" style="margin-top:22px">Masuk</button>
        </form>
        <p class="muted" style="margin-top:18px;text-align:center">Lupa akun? Hubungi pengawas ruangan.</p>
    </div>
</div>
@endsection
