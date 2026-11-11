@extends('layouts.app')

@section('title', $section->name)

@section('content')
<div class="card">
    @include('partials.participant-hello')
    <h2>Bagian berikutnya</h2>
    @include('partials.exam-rules')

    @if($setting->is_open || $setting->practiceRunning())
        <form method="post" action="{{ route('exam.start') }}" data-loading="Menyiapkan soal…">
            @csrf
            <button class="btn btn-lg btn-green btn-block">Mulai {{ $section->name }}</button>
        </form>
        <p class="muted" style="text-align:center">Waktu mulai berjalan saat soal muncul.</p>
    @else
        <div class="alert alert-err">Ujian sedang ditutup oleh panitia. Tunggu instruksi pengawas.</div>
        <button class="btn btn-gray btn-block" onclick="location.reload()">Muat ulang</button>
    @endif
</div>
@endsection

@if($section->is_practice)
    @push('scripts')
        @include('partials.practice-watch')
    @endpush
@endif
