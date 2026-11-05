@extends('layouts.app')

@section('title', $section->name)

@push('head')
<style>
    body { user-select: none; -webkit-user-select: none; }
    .public .wrap { max-width: 860px; }
    .exam-bar { position: sticky; top: 0; z-index: 30; background: linear-gradient(90deg, var(--navy), var(--blue-700) 60%, var(--blue)); color: #fff; box-shadow: 0 4px 16px rgba(36,27,94,.25); }
    .exam-bar .inner { max-width: 860px; margin: 0 auto; padding: 10px 16px; display: flex; gap: 14px; align-items: center; flex-wrap: wrap; }
    .exam-bar img { height: 42px; filter: drop-shadow(0 2px 4px rgba(0,0,0,.25)); }
    .exam-bar img.me { height: 40px; width: 40px; border-radius: 50%; filter: none; box-shadow: 0 0 0 2px rgba(255,255,255,.85); }
    .exam-bar .title { font-weight: 700; font-size: 16px; line-height: 1.2; }
    .exam-bar .sub { font-size: 12.5px; color: #cdd6ff; }
    .exam-bar .sub b { color: #fff; }
    .timer { font-size: 24px; font-weight: 800; font-variant-numeric: tabular-nums; background: rgba(255,255,255,.14); padding: 4px 14px; border-radius: 10px; letter-spacing: .03em; }
    .timer.low { background: var(--red); animation: blink 1s steps(2) infinite; }
    @keyframes blink { 50% { opacity: .75; } }
    .progress { height: 4px; background: rgba(255,255,255,.2); }
    .progress div { height: 100%; background: #fff; transition: width .3s; }
    .nav-grid { display: flex; flex-wrap: wrap; gap: 7px; }
    .nav-grid a { width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; border-radius: 10px; background: #fff; border: 1.5px solid var(--line); color: var(--navy); text-decoration: none; font-size: 14px; font-weight: 600; }
    .nav-grid a:hover { border-color: var(--blue); }
    .nav-grid a.done { background: var(--blue); border-color: var(--blue); color: #fff; }
    .nav-grid a.current { border-color: var(--navy); box-shadow: 0 0 0 2px var(--navy); }
    .step-nav { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin: 4px 0 18px; }
    .step-nav .btn:disabled { visibility: hidden; }
    body { padding-bottom: 88px; } /* last question/finish button can scroll clear of the floating button */
    .nav-fab { position: fixed; right: 18px; bottom: 18px; z-index: 40; width: 58px; height: 58px; border-radius: 50%; border: 2px solid rgba(255,255,255,.6); background: rgba(36,27,94,.82); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); color: #fff; cursor: pointer; box-shadow: 0 6px 20px rgba(36,27,94,.35); display: flex; flex-direction: column; align-items: center; justify-content: center; line-height: 1; transition: transform .12s; }
    .nav-fab:active { transform: scale(.92); }
    .nav-fab .icon { font-size: 20px; }
    .nav-fab .count { font-size: 10.5px; font-weight: 700; margin-top: 3px; }
    .nav-pop { position: fixed; right: 18px; bottom: 88px; z-index: 40; width: min(340px, calc(100vw - 36px)); max-height: 60vh; overflow-y: auto; background: #fff; border-radius: 18px; box-shadow: 0 14px 44px rgba(27,20,72,.3); padding: 14px 16px 16px; transform-origin: bottom right; animation: pop .16s ease-out; }
    .nav-pop[hidden] { display: none; }
    .nav-pop .head { display: flex; align-items: baseline; gap: 8px; margin-bottom: 10px; }
    @keyframes pop { from { transform: scale(.85); opacity: 0; } }
    .passage-card h3 { display: flex; gap: 8px; align-items: center; }
    .passage { background: var(--blue-50); border-left: 4px solid var(--blue); border-radius: 0 10px 10px 0; padding: 14px 18px; white-space: pre-line; line-height: 1.75; }
    .q { scroll-margin-top: 90px; }
    .q-num { display: inline-flex; min-width: 32px; height: 32px; padding: 0 8px; border-radius: 9px; background: var(--navy); color: #fff; font-weight: 700; align-items: center; justify-content: center; margin-right: 10px; flex-shrink: 0; }
    .q-text { display: flex; align-items: flex-start; font-weight: 600; font-size: 16px; margin-bottom: 14px; padding-top: 4px; }
    .opt { position: relative; display: flex; gap: 12px; align-items: center; padding: 11px 14px; border: 1.5px solid var(--line); border-radius: 12px; margin: 0 0 8px; cursor: pointer; font-weight: 500; transition: border-color .12s, background .12s; }
    .opt:hover { border-color: var(--blue-100); background: #fafbff; }
    .opt input { position: absolute; opacity: 0; pointer-events: none; }
    .opt .letter { width: 30px; height: 30px; border-radius: 50%; border: 1.5px solid #cfd6e6; display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--muted); flex-shrink: 0; }
    .opt:has(input:checked) { border-color: var(--blue); background: var(--blue-50); }
    .opt:has(input:checked) .letter { background: var(--blue); border-color: var(--blue); color: #fff; }
    .opt:has(input:focus-visible) { outline: 3px solid var(--blue-100); }
    .save-state { font-size: 12.5px; color: #cdd6ff; }
    #lock { position: fixed; inset: 0; z-index: 100; background: rgba(27,20,72,.97); color: #fff; display: none; align-items: center; justify-content: center; text-align: center; padding: 24px; }
    #lock h2 { color: #fff; }
    #lock.show { display: flex; }
</style>
@endpush

@section('plain', true)

@section('before')
<div class="exam-bar">
    <div class="inner">
        <img src="{{ asset('image/ocean-logo.png') }}" alt="OCEAN 2026">
        <div>
            <div class="title">{{ $section->name }}</div>
            <div class="sub">
                Terjawab <b id="answered">{{ $choices->count() }}</b>/{{ $section->questions->count() }}
                · Pelanggaran <b id="violations">{{ $violations }}</b>@if($setting->max_violations > 0)/{{ $setting->max_violations }}@endif
                · <span class="save-state" id="save-state"></span>
            </div>
        </div>
        <span class="spacer"></span>
        @if(auth()->user()->avatarUrl())<img class="me" src="{{ auth()->user()->avatarUrl() }}" alt="" title="{{ auth()->user()->name }}">@endif
        <span class="timer" id="timer" aria-label="Sisa waktu">--:--</span>
    </div>
    <div class="progress"><div id="progress" style="width:{{ $section->questions->count() ? round($choices->count() / $section->questions->count() * 100) : 0 }}%"></div></div>
</div>
@endsection

@section('content')
<button type="button" class="nav-fab" id="nav-fab" aria-label="Navigasi soal" aria-expanded="false" aria-controls="nav-pop">
    <span class="icon">☰</span><span class="count" id="fab-count">{{ $choices->count() }}/{{ $section->questions->count() }}</span>
</button>
<div class="nav-pop" id="nav-pop" hidden>
    <div class="head"><strong>Navigasi soal</strong><span class="spacer"></span><span class="muted">Biru = sudah dijawab</span></div>
    <div class="nav-grid">
        @foreach($section->questions as $i => $q)
            <a href="#" data-go="{{ $i }}" id="nav{{ $q->id }}" class="{{ $choices->has($q->id) ? 'done' : '' }}">{{ $i + 1 }}</a>
        @endforeach
    </div>
</div>

@if($section->directions)
    <div class="card"><strong>Directions:</strong> {{ $section->directions }}</div>
@endif

@php $total = $section->questions->count(); @endphp
@foreach($section->questions as $i => $q)
    @php
        if ($q->passage_id && $q->passage_id !== ($section->questions[$i - 1] ?? null)?->passage_id) {
            // Number range = this question plus the consecutive ones sharing its passage.
            $first = $last = $i;
            while (($section->questions[$last + 1] ?? null)?->passage_id === $q->passage_id) $last++;
        }
    @endphp
    <div class="step" id="step{{ $i }}" @if($i) hidden @endif>
        @if($q->passage_id)
            <div class="card passage-card">
                <h3><span class="badge badge-blue">Passage</span> {{ $q->passage->title }}</h3>
                <div class="muted">Text for question no {{ $first === $last ? $first + 1 : ($first + 1).'-'.($last + 1) }}</div>
                <div class="passage">{{ $q->passage->body }}</div>
            </div>
        @endif

        <div class="card q" id="q{{ $q->id }}">
            <div class="q-text"><span class="q-num">{{ $i + 1 }}</span><span>{{ $q->text }}</span></div>
            @foreach(\App\Models\Question::CHOICES as $c)
                <label class="opt">
                    <input type="radio" name="q{{ $q->id }}" value="{{ $c }}" data-q="{{ $q->id }}" @checked($choices->get($q->id) === $c)>
                    <span class="letter">{{ strtoupper($c) }}</span>
                    <span>{{ $q->{'option_'.$c} }}</span>
                </label>
            @endforeach
        </div>

        <div class="step-nav">
            <button type="button" class="btn btn-gray" data-go="{{ $i - 1 }}" @disabled($i === 0)>&larr; Sebelumnya</button>
            <span class="muted">Soal {{ $i + 1 }} dari {{ $total }}</span>
            <button type="button" class="btn" data-go="{{ $i + 1 }}" @disabled($i + 1 === $total)>Berikutnya &rarr;</button>
        </div>
    </div>
@endforeach

<div class="card" id="finish-card" style="text-align:center" @if($total) hidden @endif>
    <form method="post" action="{{ route('exam.finish') }}" id="finish-form">
        @csrf
        <p class="muted">Setelah dikirim, kamu tidak bisa kembali ke bagian ini.</p>
        <button type="button" class="btn btn-lg btn-green" id="finish-btn">Selesai &amp; Kirim {{ $section->name }}</button>
    </form>
</div>

<div id="lock">
    <div>
        <h2 id="lock-title">Mode layar penuh</h2>
        <p id="lock-msg">Ujian harus dikerjakan dalam layar penuh.</p>
        <button class="btn btn-lg" id="lock-btn">Masuk layar penuh &amp; lanjutkan</button>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    const urls = { answer: @json(route('exam.answer')), violation: @json(route('exam.violation')) };
    const total = {{ $section->questions->count() }};
    const end = Date.now() + {{ $remaining }} * 1000;
    const fsSupported = !!document.documentElement.requestFullscreen;
    let leaving = false, enteredFs = false, lastViolation = 0;

    const post = (url, body) => fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify(body),
    });
    // Waits for unsaved answers first. Manual finish aborts if they still can't be saved; auto-finish at 00:00 sends anyway.
    const finish = async (auto = false) => {
        leaving = true;
        loading.show('Mengirim jawaban…');
        for (let i = 0; i < 20 && Object.keys(pending).length; i++) await new Promise(r => setTimeout(r, 500));
        if (Object.keys(pending).length && !auto) {
            leaving = false;
            loading.hide();
            return toast('Masih ada jawaban yang belum tersimpan. Periksa koneksi lalu coba kirim lagi.', 'err');
        }
        document.getElementById('finish-form').submit();
    };

    // --- timer (server decides the real deadline; this only displays it) ---
    const timerEl = document.getElementById('timer');
    const tick = () => {
        const left = Math.max(0, Math.round((end - Date.now()) / 1000));
        timerEl.textContent = String(Math.floor(left / 60)).padStart(2, '0') + ':' + String(left % 60).padStart(2, '0');
        timerEl.classList.toggle('low', left <= 300);
        if (left === 0 && !leaving) finish(true);
    };
    tick();
    setInterval(tick, 1000);

    // --- floating question navigator ---
    const fab = document.getElementById('nav-fab'), pop = document.getElementById('nav-pop');
    const setNav = open => { pop.hidden = !open; fab.setAttribute('aria-expanded', open); };
    fab.addEventListener('click', e => { e.stopPropagation(); setNav(pop.hidden); });
    pop.querySelectorAll('a').forEach(a => a.addEventListener('click', () => setNav(false)));
    document.addEventListener('click', e => { if (!pop.hidden && !pop.contains(e.target)) setNav(false); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') setNav(false); });

    // --- one question per page; opens at the first unanswered question ---
    const steps = document.querySelectorAll('.step'), finishCard = document.getElementById('finish-card');
    let cur = 0;
    const go = i => {
        if (i < 0 || i >= steps.length) return;
        steps[cur].hidden = true;
        steps[i].hidden = false;
        cur = i;
        finishCard.hidden = i !== steps.length - 1;
        document.querySelectorAll('.nav-grid a').forEach((a, j) => a.classList.toggle('current', j === i));
        window.scrollTo(0, 0);
    };
    document.addEventListener('click', e => {
        const t = e.target.closest('[data-go]');
        if (t) { e.preventDefault(); go(+t.dataset.go); }
    });
    const firstOpen = [...steps].findIndex(s => !s.querySelector('input:checked'));
    go(firstOpen === -1 ? steps.length - 1 : firstOpen);

    // --- autosave answers, retrying until the server confirms ---
    const pending = {};
    const saveState = document.getElementById('save-state');
    const flush = async () => {
        for (const [q, choice] of Object.entries(pending)) {
            try {
                const res = await post(urls.answer, { question_id: +q, choice });
                if (res.status === 409) { leaving = true; return location.reload(); } // section closed
                if (!res.ok) throw new Error(res.status);
                if (pending[q] === choice) delete pending[q];
            } catch (e) {
                if (!saveState.textContent.startsWith('⚠')) toast('Jawaban gagal tersimpan. Periksa koneksi, sistem mencoba lagi otomatis.', 'err');
                saveState.textContent = '⚠ Gagal menyimpan, mencoba lagi…';
                return setTimeout(flush, 1500 + Math.random() * 3000); // jitter so retries don't arrive in one wave
            }
        }
        saveState.textContent = '✓ Tersimpan';
    };
    document.querySelectorAll('input[type=radio]').forEach(r => r.addEventListener('change', () => {
        pending[r.dataset.q] = r.value;
        document.getElementById('nav' + r.dataset.q).classList.add('done');
        const done = document.querySelectorAll('.nav-grid a.done').length;
        document.getElementById('answered').textContent = done;
        document.getElementById('fab-count').textContent = done + '/' + total;
        document.getElementById('progress').style.width = Math.round(done / total * 100) + '%';
        saveState.textContent = 'Menyimpan…';
        flush();
    }));

    // --- finish button with inline confirmation ---
    const finishBtn = document.getElementById('finish-btn');
    finishBtn.addEventListener('click', () => {
        if (finishBtn.dataset.confirm) return finish();
        const unanswered = total - document.querySelectorAll('.nav-grid a.done').length;
        finishBtn.dataset.confirm = 1;
        finishBtn.textContent = (unanswered ? unanswered + ' soal belum dijawab. ' : '') + 'Klik sekali lagi untuk kirim';
        finishBtn.classList.replace('btn-green', 'btn-red');
    });

    // --- anti-cheat: fullscreen + leaving the page counts as a violation ---
    const lock = document.getElementById('lock');
    const showLock = (title, msg) => {
        document.getElementById('lock-title').textContent = title;
        document.getElementById('lock-msg').textContent = msg;
        lock.classList.add('show');
    };
    const violation = async (reason) => {
        if (leaving || Date.now() - lastViolation < 2000) return; // one action fires several events
        lastViolation = Date.now();
        try {
            const data = await (await post(urls.violation, { reason })).json();
            document.getElementById('violations').textContent = data.violations;
            if (data.locked) { leaving = true; return location.reload(); }
            const left = data.max > 0 ? ` Sisa kesempatan: ${data.max - data.violations}.` : '';
            showLock('Pelanggaran tercatat!', `Kamu meninggalkan halaman ujian (${reason}).${left}`);
        } catch (e) {}
    };

    document.getElementById('lock-btn').addEventListener('click', () => {
        if (!fsSupported) return lock.classList.remove('show');
        document.documentElement.requestFullscreen().then(() => lock.classList.remove('show')).catch(() => {});
    });
    if (fsSupported) {
        showLock('Mode layar penuh', 'Ujian harus dikerjakan dalam layar penuh. Waktu tetap berjalan.');
        document.addEventListener('fullscreenchange', () => {
            if (document.fullscreenElement) { enteredFs = true; return; }
            if (enteredFs) violation('keluar layar penuh');
            if (!leaving) showLock('Mode layar penuh', 'Kembali ke layar penuh untuk melanjutkan.');
        });
    }
    document.addEventListener('visibilitychange', () => document.hidden && violation('pindah tab/aplikasi'));
    window.addEventListener('blur', () => violation('pindah jendela'));

    ['contextmenu', 'copy', 'cut', 'paste', 'dragstart'].forEach(ev => document.addEventListener(ev, e => e.preventDefault()));
    document.addEventListener('keydown', e => {
        if (e.key === 'F12' || (e.ctrlKey && ['c', 'u', 'p', 's'].includes(e.key.toLowerCase()))) e.preventDefault();
    });
    window.addEventListener('beforeunload', () => { leaving = true; });
})();
</script>
@endpush
