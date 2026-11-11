@extends('layouts.admin')

@section('title', 'Detail Peserta')

@section('content')
<div class="card" style="display:flex;gap:16px;align-items:center;flex-wrap:wrap">
    <div class="avatar" style="width:54px;height:54px;font-size:22px">{{ mb_strtoupper(mb_substr($participant->name, 0, 1)) }}</div>
    <div>
        <h2 style="margin:0">{{ $participant->name }}</h2>
        <div class="muted">{{ $participant->username }}@if($participant->school) · {{ $participant->school }}@endif</div>
    </div>
    <span class="badge {{ $participant->violations ? 'badge-red' : '' }}">{{ $participant->violations }} pelanggaran</span>
    <span class="spacer"></span>
    <form class="inline" method="post" action="{{ route('admin.participants.reset-device', $participant) }}">@csrf<button class="btn btn-sm btn-gray">Reset perangkat</button></form>
    <form class="inline" method="post" action="{{ route('admin.participants.reset-exam', $participant) }}" data-confirm="Semua jawaban {{ $participant->username }} dihapus dan ujiannya dimulai ulang dari awal dengan waktu penuh." data-confirm-title="Reset ujian peserta?" data-confirm-ok="Reset ujian">@csrf<button class="btn btn-sm btn-red">Reset ujian</button></form>
</div>

@if($participant->violationLogs->isNotEmpty())
    <div class="card">
        <h3>Riwayat pelanggaran</h3>
        <div class="table-wrap">
        <table>
            <thead><tr><th>Waktu</th><th>Bagian</th><th>Jenis</th></tr></thead>
            <tbody>
            @foreach($participant->violationLogs as $log)
                <tr><td>{{ $log->created_at->format('H:i:s') }}</td><td>{{ $log->section?->name ?? '-' }}</td><td>{{ $log->reason }}</td></tr>
            @endforeach
            </tbody>
        </table>
        </div>
    </div>
@endif

@foreach($sections as $section)
    @php
        $attempt = $participant->attempts->firstWhere('section_id', $section->id);
        $choices = $attempt ? $attempt->answers->pluck('choice', 'question_id') : collect();
    @endphp
    <div class="card">
        <div class="card-head">
            <h3>{{ $section->name }}</h3>
            <span class="spacer"></span>
            @if($attempt)<span class="muted">Mulai {{ $attempt->started_at->format('H:i:s') }} · Selesai {{ $attempt->finished_at?->format('H:i:s') ?? '-' }}</span>@endif
        </div>
        @if(! $attempt)
            <p class="muted">Belum dikerjakan.</p>
        @else
            <div class="table-wrap">
            <table>
                <thead><tr><th>No</th><th>Soal</th><th>Jawaban</th><th>Kunci</th><th>Hasil</th></tr></thead>
                <tbody>
                @foreach($section->questions as $i => $q)
                    @php $c = $choices->get($q->id); @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($q->text, 90) }}</td>
                        <td><strong>{{ $c ? strtoupper($c) : '-' }}</strong></td>
                        <td>{{ strtoupper($q->answer) }}</td>
                        <td>@if(! $c)<span class="badge">kosong</span>@elseif($c === $q->answer)<span class="badge badge-green">benar</span>@else<span class="badge badge-red">salah</span>@endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
@endforeach
@endsection
