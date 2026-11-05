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
    .game { margin: 14px 0; }
    .i { width: 18px; height: 18px; vertical-align: -3px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }
    .game .btn, .chip, .pill, .game-head b, .game .msg { display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
    .game-offer { padding: 18px 18px 20px; border: 2px dashed var(--blue-100); border-radius: var(--radius); background: var(--blue-50); text-align: center; }
    .game-offer img { display: block; width: min(100%, 300px); height: auto; margin: 0 auto 12px; }
    .game-offer b { display: block; color: var(--navy); font-size: 17px; }
    .game-offer span { display: block; color: var(--muted); font-size: 14px; margin: 2px 0 14px; }
    .game-box { border: 1px solid var(--line); border-radius: var(--radius); overflow: hidden; }
    .game-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 12px 16px; background: linear-gradient(135deg, var(--navy), var(--blue)); color: #fff; }
    .game-head b { font-size: 16px; }
    .pill { background: rgba(255,255,255,.18); border-radius: 999px; padding: 4px 12px; font-size: 13px; font-weight: 600; white-space: nowrap; }
    .game-body { padding: 18px 16px; text-align: center; }
    .chips { display: flex; gap: 6px; justify-content: center; flex-wrap: wrap; margin-bottom: 18px; }
    .chip { background: var(--blue-50); color: var(--blue-700); border-radius: 999px; padding: 4px 12px; font-size: 13px; font-weight: 600; }
    .slots, .tiles { display: flex; flex-wrap: wrap; justify-content: center; gap: 6px; }
    .slots { margin-bottom: 18px; min-height: 48px; }
    .slots button, .tiles button { width: 42px; height: 48px; padding: 0; border-radius: 10px; font-size: 22px; font-weight: 800; text-transform: uppercase; cursor: pointer; transition: transform .12s, background .2s, opacity .2s; }
    .slots button { border: 2px dashed var(--blue-100); background: #fff; color: var(--navy); }
    .slots button.filled { border-style: solid; border-color: var(--blue); }
    .tiles button { border: 0; background: var(--navy); color: #fff; box-shadow: 0 3px 0 var(--navy-800); }
    .tiles button:active { transform: translateY(2px); box-shadow: 0 1px 0 var(--navy-800); }
    .tiles button.used { opacity: .15; pointer-events: none; }
    .slots.win button { background: var(--green); border-color: var(--green); color: #fff; animation: pop .35s; }
    .slots.lose button { border-color: var(--red); color: var(--red); animation: shake .35s; }
    @keyframes pop { 50% { transform: scale(1.15); } }
    @keyframes shake { 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }
    .game-actions { display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; margin-top: 18px; }
    .btn.btn-soft { background: var(--blue-50); color: var(--blue-700); }
    .btn.btn-soft:hover { background: var(--blue-100); }
    .btn.btn-soft:disabled { opacity: .45; cursor: not-allowed; }
    .game [hidden] { display: none !important; }
    .game .msg { min-height: 22px; font-weight: 700; margin: 14px 0 0; color: var(--navy); }
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
    <div class="game">
        <div class="game-offer" id="gameOffer">
            <img src="{{ asset('image/ocean-characters.png') }}" alt="" width="2082" height="703">
            <b>Sambil menunggu, mau main game dulu?</b>
            <span>Susun huruf acak jadi kata bahasa Inggris bertema laut dan pertanian.</span>
            <button type="button" class="btn" id="gameStart"><svg class="i" viewBox="0 0 24 24"><path d="M8 5l11 7-11 7z"/></svg>Main Sekarang</button>
        </div>
        <div class="game-box" id="gameBox" hidden>
            <div class="game-head">
                <b><svg class="i" viewBox="0 0 24 24"><rect x="3" y="3" width="8" height="8" rx="2"/><rect x="13" y="3" width="8" height="8" rx="2"/><rect x="3" y="13" width="8" height="8" rx="2"/><rect x="13" y="13" width="8" height="8" rx="2" fill="currentColor"/></svg>Susun Kata</b>
                <span class="pill"><svg class="i" viewBox="0 0 24 24" style="fill:#facc15;stroke:#facc15"><path d="M12 2l3.1 6.3 6.9 1-5 4.8 1.2 6.9-6.2-3.2-6.2 3.2L7 14.1 2 9.3l6.9-1z"/></svg>Skor <span id="score">0</span></span>
            </div>
            <div class="game-body">
                <div class="chips"><span class="chip" id="cat"></span><span class="chip" id="mean"></span></div>
                <div class="slots" id="slots" aria-label="Jawaban"></div>
                <div class="tiles" id="tiles"></div>
                <p class="msg" id="msg" role="status"></p>
                <div class="game-actions">
                    <button type="button" class="btn btn-soft" id="hint"><svg class="i" viewBox="0 0 24 24"><path d="M9 18h6M10 22h4M12 2a7 7 0 0 0-4 12.7V16h8v-1.3A7 7 0 0 0 12 2z"/></svg>Petunjuk (<span id="hintLeft">3</span>)</button>
                    <button type="button" class="btn btn-soft" id="reshuffle"><svg class="i" viewBox="0 0 24 24"><path d="M16 3h5v5M4 20L21 3M21 16v5h-5M15 15l6 6M4 4l5 5"/></svg>Acak</button>
                    <button type="button" class="btn btn-soft" id="clear"><svg class="i" viewBox="0 0 24 24"><path d="M21 5H8l-6 7 6 7h13a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1zM12 9l6 6M18 9l-6 6"/></svg>Hapus</button>
                    <button type="button" class="btn btn-soft" id="skip"><svg class="i" viewBox="0 0 24 24"><path d="M5 4l10 8-10 8zM19 5v14"/></svg>Lewati</button>
                </div>
            </div>
        </div>
    </div>
    @include('partials.exam-rules')
</div>
@endsection

@push('scripts')
<script>
    // Waiting-room word scramble; client only, no server requests. Hidden until the participant opts in.
    (() => {
        const WORDS = [
            ['coral','Marine','karang'], ['whale','Marine','paus'], ['shark','Marine','hiu'], ['ocean','Marine','samudra'],
            ['seaweed','Marine','rumput laut'], ['turtle','Marine','penyu'], ['pearl','Marine','mutiara'], ['squid','Marine','cumi-cumi'],
            ['island','Marine','pulau'], ['beach','Marine','pantai'], ['dolphin','Marine','lumba-lumba'], ['shrimp','Marine','udang'],
            ['octopus','Marine','gurita'], ['harbor','Marine','pelabuhan'], ['fisherman','Marine','nelayan'], ['mangrove','Marine','bakau'],
            ['wheat','Agro','gandum'], ['harvest','Agro','panen'], ['seeds','Agro','benih'], ['farmer','Agro','petani'],
            ['plant','Agro','tanaman'], ['mango','Agro','mangga'], ['corn','Agro','jagung'], ['coffee','Agro','kopi'],
            ['spice','Agro','rempah'], ['fruit','Agro','buah'], ['honey','Agro','madu'], ['tractor','Agro','traktor'],
            ['fertilizer','Agro','pupuk'], ['paddy','Agro','padi'], ['coconut','Agro','kelapa'], ['vegetable','Agro','sayuran'],
        ];
        const svg = d => `<svg class="i" viewBox="0 0 24 24"><path d="${d}"/></svg>`;
        const ICON = {
            Marine: svg('M2 9c2-2 4-2 6 0s4 2 6 0 4-2 6 0M2 15c2-2 4-2 6 0s4 2 6 0 4-2 6 0'),
            Agro: svg('M12 21V11M12 11C12 6 8 3 3 3c0 5 4 8 9 8zM12 14c0-4 3-7 8-7 0 4-3 7-8 7z'),
            hint: svg('M9 18h6M10 22h4M12 2a7 7 0 0 0-4 12.7V16h8v-1.3A7 7 0 0 0 12 2z'),
            ok: svg('M20 6L9 17l-5-5'),
            no: svg('M18 6L6 18M6 6l12 12'),
            eye: svg('M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12zM12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6z'),
        };
        const say = (text, icon) => { msg.innerHTML = (icon ? ICON[icon] : '') + '<span></span>'; msg.lastChild.textContent = text; };
        const $ = id => document.getElementById(id);
        const slotsEl = $('slots'), tilesEl = $('tiles'), msg = $('msg');
        const order = WORDS.map((_, i) => i).sort(() => Math.random() - .5);
        let next = 0, score = 0, answer, mean, picked, locked; // picked = letter tiles in answer order
        let hints = 3, streak = 0, hinted; // +1 hint for every 3 words solved without one

        const shuffle = w => { let s; do s = [...w].sort(() => Math.random() - .5).join(''); while (s === w); return s; };

        function sync() {
            [...tilesEl.children].forEach(t => t.classList.toggle('used', picked.includes(t)));
            slotsEl.className = 'slots';
            slotsEl.innerHTML = '';
            [...answer].forEach((_, i) => {
                const b = document.createElement('button');
                b.type = 'button'; b.textContent = picked[i]?.textContent || ''; b.className = picked[i] ? 'filled' : '';
                b.onclick = () => { if (!locked && picked[i]) { picked.splice(i, 1); sync(); } };
                slotsEl.append(b);
            });
            if (picked.length === answer.length) check();
        }

        function check() {
            locked = true;
            if (picked.map(t => t.textContent).join('') === answer) {
                $('score').textContent = ++score;
                let bonus = '';
                if (!hinted && ++streak % 3 === 0) { hints++; bonus = ' +1 petunjuk!'; }
                slotsEl.classList.add('win'); say(`Benar! ${answer} = ${mean}.${bonus}`, 'ok');
                updateHint();
                setTimeout(nextWord, 1200);
            } else {
                slotsEl.classList.add('lose'); say('Belum tepat, coba lagi.', 'no');
                setTimeout(() => { picked = []; locked = false; sync(); }, 700);
            }
        }

        function deal(letters) {
            picked = [];
            tilesEl.innerHTML = '';
            [...letters].forEach(c => {
                const b = document.createElement('button');
                b.type = 'button'; b.textContent = c;
                b.onclick = () => { if (!locked) { picked.push(b); sync(); } };
                tilesEl.append(b);
            });
            sync();
        }

        function nextWord() {
            const [w, cat, m] = WORDS[order[next++ % order.length]];
            answer = w; mean = m; locked = false; hinted = false;
            $('cat').innerHTML = ICON[cat] + cat + ' · ' + w.length + ' huruf';
            $('mean').hidden = true;
            updateHint();
            say('Ketuk huruf atau ketik dengan keyboard.');
            deal(shuffle(w));
        }

        $('gameStart').onclick = () => { $('gameOffer').hidden = true; $('gameBox').hidden = false; nextWord(); };
        function updateHint() {
            $('hintLeft').textContent = hints;
            $('hint').disabled = hinted || hints === 0;
        }
        $('hint').onclick = () => {
            if (locked || hinted || !hints) return;
            hints--; hinted = true; streak = 0;
            $('mean').innerHTML = ICON.hint + '<span></span>'; $('mean').lastChild.textContent = mean;
            $('mean').hidden = false;
            updateHint();
        };
        $('reshuffle').onclick =() => { if (!locked) deal(shuffle(answer)); };
        $('clear').onclick = () => { if (!locked) { picked.pop(); sync(); } };
        $('skip').onclick = () => {
            if (locked) return;
            locked = true; streak = 0; say(`Jawabannya: ${answer.toUpperCase()} (${mean})`, 'eye');
            setTimeout(nextWord, 1500);
        };
        document.addEventListener('keydown', e => {
            if ($('gameBox').hidden || locked || e.ctrlKey || e.metaKey || e.altKey) return;
            if (e.key === 'Backspace') { e.preventDefault(); picked.pop(); return sync(); }
            const t = [...tilesEl.children].find(t => t.textContent === e.key.toLowerCase() && !picked.includes(t));
            if (t) { picked.push(t); sync(); }
        });
    })();

    setInterval(async () => {
        try {
            const res = await fetch(@json(route('exam.status')), { headers: { Accept: 'application/json' } });
            if (res.redirected || res.status === 401) return location.reload(); // session ended
            if ((await res.json()).open) { loading.show('Ujian dimulai! Menyiapkan soal…'); location.reload(); }
        } catch (e) {}
    }, 5000); // 200 participants ≈ 40 req/s, sized for shared-hosting PHP workers
</script>
@endpush
