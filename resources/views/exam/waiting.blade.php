@extends('layouts.app')

@section('title', 'Ruang Tunggu')

@push('head')
<style>
    .waiting { display: flex; align-items: center; gap: 16px; background: linear-gradient(135deg, var(--navy), var(--blue)); color: #fff; border-radius: 14px; padding: 18px 20px; margin-bottom: 8px; }
    .waiting h2 { color: #fff; margin: 0; font-size: 19px; }
    .waiting p { margin: 2px 0 0; color: #dbe4ff; font-size: 14px; }
    .pulse { width: 46px; height: 46px; border-radius: 50%; background: rgba(255,255,255,.18); flex-shrink: 0; position: relative; }
    .pulse::before, .pulse::after { content: ''; position: absolute; inset: 13px; border-radius: 50%; background: #fff; }
    .pulse::after { inset: 0; background: rgba(255,255,255,.35); animation: pulse 1.6s ease-out infinite; }
    @keyframes pulse { from { transform: scale(.4); opacity: 1; } to { transform: scale(1.3); opacity: 0; } }
</style>
@endpush

@section('content')
<div class="card">
    @include('partials.participant-hello')
    <div class="waiting">
        <div class="pulse"></div>
        <div>
            <h2>Menunggu panitia memulai ujian</h2>
            <p>Halaman ini otomatis masuk ke soal begitu ujian dimulai. Jangan tutup halaman ini.</p>
        </div>
    </div>
    @include('partials.exam-rules')
</div>
@endsection

@push('scripts')
<script>
    setInterval(async () => {
        try {
            const res = await fetch(@json(route('exam.status')), { headers: { Accept: 'application/json' } });
            if (res.redirected || res.status === 401) return location.reload(); // session ended
            if ((await res.json()).open) { loading.show('Ujian dimulai! Menyiapkan soal…'); location.reload(); }
        } catch (e) {}
    }, 5000); // 200 participants ≈ 40 req/s, sized for shared-hosting PHP workers
</script>
@endpush
