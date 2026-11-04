@extends('layouts.app')

@section('title', 'Selesai')
@section('hide-logo', true)

@section('content')
<div class="card" style="text-align:center;max-width:560px;margin:4vh auto 0;overflow:hidden;padding-top:0">
    <div style="background:linear-gradient(160deg,var(--navy),var(--blue));margin:0 -28px 22px;padding:26px 20px 0">
        <img src="{{ asset('image/ocean-wordmark.png') }}" alt="OCEAN 2026" style="width:200px;filter:drop-shadow(0 6px 14px rgba(0,0,0,.3))">
        <img src="{{ asset('image/ocean-characters.png') }}" alt="" style="display:block;width:100%;margin-top:8px">
    </div>
    <div style="width:56px;height:56px;margin:0 auto 12px;border-radius:50%;background:var(--green-50);color:var(--green);display:flex;align-items:center;justify-content:center">
        <svg viewBox="0 0 24 24" width="32" height="32" fill="currentColor"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg>
    </div>
    <h2>Terima kasih, {{ auth()->user()->name }}!</h2>
    <p>Semua jawabanmu sudah terkirim.<br>Hasil akan diumumkan oleh panitia.</p>
    <form method="post" action="{{ route('logout') }}" style="margin-top:18px">@csrf<button class="btn btn-gray">Logout</button></form>
</div>
@endsection

@push('scripts')
<script>document.fullscreenElement && document.exitFullscreen?.();</script>
@endpush
