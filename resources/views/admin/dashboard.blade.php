@extends('layouts.admin')

@section('title', 'Dashboard')

@push('head')<meta http-equiv="refresh" content="15">@endpush

@section('actions')
    <span class="muted">Auto-refresh 15 detik</span>
    <a class="btn btn-sm btn-gray" href="{{ route('admin.export') }}" data-download data-loading="Menyiapkan hasil nilai…">Export Excel (CSV)</a>
@endsection

@section('content')
@php
    $finished = $rows->where('finished', true)->count();
    $active = $rows->filter(fn ($r) => collect($r['sections'])->contains('status', 'mengerjakan'))->count();
@endphp

<div class="card banner">
    <img class="banner-chars" src="{{ asset('image/ocean-characters.png') }}" alt="">
    <svg class="banner-wave" viewBox="0 0 1440 40" preserveAspectRatio="none" aria-hidden="true"><path d="M0 20c120-18 240-18 360 0s240 18 360 0 240-18 360 0 240 18 360 0v20H0z" fill="rgba(255,255,255,.08)"/></svg>
    <div>
        <div style="font-size:13px;color:#cdd6ff;font-weight:600">STATUS UJIAN</div>
        <div style="font-size:22px;font-weight:800">
            {{ $setting->is_open ? 'Sedang berlangsung' : 'Belum dimulai / ditutup' }}
        </div>
        @if($setting->is_open && $setting->opened_at)<div style="font-size:13px;color:#cdd6ff">Dimulai pukul {{ $setting->opened_at->format('H:i:s') }}</div>@endif
    </div>
    <span class="spacer"></span>
    <form method="post" action="{{ route('admin.toggle-open') }}"
        @if($setting->is_open)
            data-confirm="Peserta yang sedang mengerjakan tidak bisa menyimpan jawaban lagi sampai ujian dibuka kembali." data-confirm-title="Tutup ujian?" data-confirm-ok="Tutup ujian" data-confirm-tone="red"
        @else
            data-confirm="Timer {{ $sections->first()?->name }} semua peserta langsung berjalan." data-confirm-title="Mulai ujian sekarang?" data-confirm-ok="Mulai ujian" data-confirm-tone="green"
        @endif>
        @csrf
        <button class="btn btn-lg {{ $setting->is_open ? 'btn-red' : 'btn-green' }}">
            {{ $setting->is_open ? 'Tutup ujian' : '▶ MULAI ujian (serentak)' }}</button>
    </form>
</div>

<div class="stats">
    <div class="stat"><div class="label">Total peserta</div><div class="value">{{ $rows->count() }}</div></div>
    <div class="stat"><div class="label">Sedang mengerjakan</div><div class="value">{{ $active }}</div></div>
    <div class="stat"><div class="label">Selesai</div><div class="value">{{ $finished }}</div></div>
    <div class="stat"><div class="label">Nilai tertinggi</div><div class="value">{{ $rows->isNotEmpty() ? $rows->max('total') + 0 : '-' }}</div></div>
</div>

<div class="card">
    <div class="card-head"><h3>Peringkat & Monitoring</h3><span class="spacer"></span><span class="muted">B = benar · S = salah · K = kosong</span></div>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>#</th><th>Peserta</th>
                @foreach($sections as $s)<th>{{ $s->name }}</th>@endforeach
                <th>Total</th><th>Waktu</th><th>Pelanggaran</th><th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($rows as $row)
            <tr>
                <td><span class="rank {{ $row['rank'] <= 3 && $row['total'] > 0 ? 'rank-'.$row['rank'] : '' }}">{{ $row['rank'] }}</span></td>
                <td><strong>{{ $row['user']->name }}</strong><div class="muted">{{ $row['user']->username }}@if($row['user']->school) · {{ $row['user']->school }}@endif</div></td>
                @foreach($sections as $s)
                    @php $r = $row['sections'][$s->id]; @endphp
                    <td>
                        @if($r['status'] === 'belum')
                            <span class="badge">belum</span>
                        @else
                            <strong style="font-size:16px">{{ $r['score'] + 0 }}</strong>
                            <span class="badge {{ $r['status'] === 'selesai' ? 'badge-green' : 'badge-yellow' }}">{{ $r['status'] }}</span>
                            <div class="muted">B{{ $r['correct'] }} S{{ $r['wrong'] }} K{{ $r['blank'] }} · {{ $r['answered'] }}/{{ $r['total_questions'] }}</div>
                        @endif
                    </td>
                @endforeach
                <td><strong style="font-size:18px;color:var(--navy)">{{ $row['total'] + 0 }}</strong></td>
                <td>{{ gmdate('H:i:s', $row['seconds']) }}</td>
                <td>@if($row['user']->violations)<span class="badge badge-red">{{ $row['user']->violations }}</span>@else <span class="muted">0</span> @endif</td>
                <td class="actions"><a class="btn btn-sm btn-gray" href="{{ route('admin.participants.show', $row['user']) }}">Detail</a></td>
            </tr>
        @empty
            <tr><td colspan="99" class="muted">Belum ada peserta. Tambahkan di menu Peserta.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    <p class="muted" style="margin-bottom:0">Peringkat: total nilai tertinggi; jika sama, total waktu pengerjaan tercepat.</p>
</div>
@endsection
