<!doctype html>
<html lang="id">
<head>
    @include('partials.head')
    <title>Lobby · OCEAN 2026</title>
    <style>
        body { margin: 0; min-height: 100vh; background: radial-gradient(1200px 600px at 50% -10%, #33489a 0%, var(--navy) 55%, var(--navy-800) 100%); color: #fff; overflow-x: hidden; }
        .top { display: flex; align-items: center; gap: 24px; padding: 28px 40px 8px; flex-wrap: wrap; }
        .top img { height: 64px; filter: drop-shadow(0 4px 10px rgba(0,0,0,.3)); }
        .top h1 { color: #fff; margin: 0; font-size: 30px; letter-spacing: .5px; }
        .top p { margin: 2px 0 0; color: #b9c4f2; font-size: 16px; }
        .count { margin-left: auto; text-align: right; }
        .count b { display: block; font-size: 64px; line-height: 1; font-variant-numeric: tabular-nums; }
        .count b small { font-size: 30px; color: #b9c4f2; font-weight: 600; }
        .count span { color: #b9c4f2; font-size: 15px; letter-spacing: 1px; text-transform: uppercase; }
        .bar { height: 6px; margin: 18px 40px 0; border-radius: 99px; background: rgba(255,255,255,.12); overflow: hidden; }
        .bar div { height: 100%; width: 0; background: linear-gradient(90deg, #6ee7b7, #34d399); border-radius: 99px; transition: width .6s; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(132px, 1fr)); gap: 22px 16px; padding: 30px 40px 140px; }
        .p { text-align: center; animation: in .5s cubic-bezier(.2,1.4,.4,1) both; }
        .p .av { width: 84px; height: 84px; margin: 0 auto 10px; border-radius: 50%; box-shadow: 0 0 0 3px rgba(255,255,255,.9), 0 8px 20px rgba(0,0,0,.25); background: var(--blue); display: flex; align-items: center; justify-content: center; font-size: 34px; font-weight: 800; overflow: hidden; }
        .p .av img { width: 100%; height: 100%; display: block; }
        .p .name { font-weight: 700; font-size: 15px; line-height: 1.25; overflow-wrap: anywhere; }
        .p .school { color: #b9c4f2; font-size: 12.5px; margin-top: 2px; }
        .badge { display: inline-block; margin-top: 6px; padding: 3px 10px; border-radius: 99px; font-size: 12px; font-weight: 700; letter-spacing: .3px; }
        .badge.work { background: #3b82f6; color: #fff; }
        .badge.break { background: #fbbf24; color: #3b2a00; }
        .badge.done { background: #22c55e; color: #fff; }
        .badge.locked { background: #ef4444; color: #fff; }
        .badge.absent { background: rgba(255,255,255,.16); color: #cdd6ff; }
        .p.absent { opacity: .45; }
        .p.done .av { box-shadow: 0 0 0 3px #22c55e, 0 8px 20px rgba(0,0,0,.25); }
        .p.locked .av { box-shadow: 0 0 0 3px #ef4444, 0 8px 20px rgba(0,0,0,.25); }
        @keyframes in { from { opacity: 0; transform: scale(.4) translateY(20px); } }
        .empty { grid-column: 1 / -1; text-align: center; color: #b9c4f2; font-size: 20px; padding: 60px 0; }
        .dock { position: fixed; left: 0; right: 0; bottom: 0; display: flex; gap: 12px; align-items: center; padding: 18px 40px; background: linear-gradient(transparent, rgba(16,11,48,.85) 40%); }
        .dock .state { font-size: 17px; color: #dbe4ff; display: flex; align-items: center; gap: 10px; }
        .dot { width: 12px; height: 12px; border-radius: 50%; background: #fbbf24; box-shadow: 0 0 0 0 rgba(251,191,36,.6); animation: ping 1.8s infinite; }
        .dot.live { background: #34d399; box-shadow: 0 0 0 0 rgba(52,211,153,.6); }
        @keyframes ping { 70% { box-shadow: 0 0 0 12px rgba(0,0,0,0); } }
        .dock .spacer { flex: 1; }
        .ghost { background: rgba(255,255,255,.12); color: #fff; border: 0; border-radius: 10px; padding: 12px 18px; font: inherit; font-weight: 600; cursor: pointer; text-decoration: none; }
        .ghost:hover { background: rgba(255,255,255,.2); }
        .go { background: #22c55e; color: #fff; border: 0; border-radius: 12px; padding: 16px 30px; font: inherit; font-size: 19px; font-weight: 800; letter-spacing: .5px; cursor: pointer; box-shadow: 0 6px 0 #15803d, 0 12px 24px rgba(0,0,0,.3); }
        .go:active { transform: translateY(3px); box-shadow: 0 3px 0 #15803d; }
        .go[hidden] { display: none; }
        #cd { position: fixed; inset: 0; z-index: 50; display: none; align-items: center; justify-content: center; background: rgba(16,11,48,.92); }
        #cd.show { display: flex; }
        #cd span { font-size: min(42vw, 420px); font-weight: 900; line-height: 1; text-shadow: 0 20px 60px rgba(0,0,0,.45); animation: tick 1s ease-out both; font-variant-numeric: tabular-nums; }
        #cd span.mulai { font-size: min(18vw, 220px); color: #6ee7b7; letter-spacing: 4px; }
        @keyframes tick { from { transform: scale(1.8); opacity: 0; } 25% { transform: scale(1); opacity: 1; } 80% { opacity: 1; } to { transform: scale(.85); opacity: 0; } }
        @media (max-width: 700px) { .top, .grid, .dock { padding-left: 16px; padding-right: 16px; } .bar { margin: 14px 16px 0; } .count b { font-size: 44px; } }
    </style>
</head>
<body>
<header class="top">
    <img src="{{ asset('image/ocean-logo.png') }}" alt="OCEAN 2026">
    <div>
        <h1 id="title">Ruang Tunggu Peserta</h1>
        <p>Olympiad of Competency in English, Agro, and Marine</p>
    </div>
    <div class="count" aria-live="polite"><b><span id="present">0</span><small> / <span id="total">0</span></small></b><span id="countLabel">peserta hadir</span></div>
</header>
<div class="bar"><div id="bar"></div></div>

<main class="grid" id="grid"><div class="empty">Menunggu peserta masuk…</div></main>

<div class="dock">
    <div class="state"><span class="dot" id="dot"></span><span id="state">Menunggu panitia memulai ujian</span></div>
    <span class="spacer"></span>
    <a class="ghost" href="{{ route('admin.dashboard') }}">Dashboard</a>
    <button type="button" class="ghost" id="fs">Layar penuh</button>
    <button type="button" class="go" id="go" @if($setting->is_open) hidden @endif>MULAI UJIAN</button>
</div>

<div id="cd" aria-live="assertive"></div>

<script>
(() => {
    const $ = id => document.getElementById(id);
    const shown = new Map(); // id => element, so only newcomers animate in
    let open = @json($setting->is_open);

    function setOpen(v) {
        open = v;
        $('go').hidden = v;
        $('dot').classList.toggle('live', v);
        $('state').textContent = v ? 'Ujian sedang berlangsung' : 'Menunggu panitia memulai ujian';
    }

    function card(p) {
        const el = document.createElement('div');
        el.className = 'p';
        const av = document.createElement('div');
        av.className = 'av';
        if (p.avatar) { const img = new Image(); img.src = p.avatar; img.alt = ''; av.append(img); }
        else av.textContent = (p.name || '?').trim().charAt(0).toUpperCase();
        const name = document.createElement('div'); name.className = 'name'; name.textContent = p.name;
        el.append(av, name);
        if (p.school) { const s = document.createElement('div'); s.className = 'school'; s.textContent = p.school; el.append(s); }
        if (p.status) {
            const [label, kind] = p.status;
            const b = document.createElement('span'); b.className = 'badge ' + kind; b.textContent = label;
            el.append(b); el.classList.add(kind);
        }
        el.dataset.key = key(p);
        return el;
    }
    const key = p => [p.id, p.avatar, p.status?.[0]].join('|'); // re-render when avatar or status changes

    async function refresh() {
        try {
            const res = await fetch(@json(route('admin.lobby.data')), { headers: { Accept: 'application/json' } });
            if (res.status === 401 || res.redirected) return location.reload();
            const d = await res.json();
            const grid = $('grid'), ids = new Set(d.present.map(p => p.id));
            for (const [id, el] of shown) if (!ids.has(id)) { el.remove(); shown.delete(id); }
            d.present.forEach(p => {
                const old = shown.get(p.id);
                if (old && old.dataset.key === key(p)) return;
                const el = card(p);
                if (old) { el.style.animation = 'none'; old.replaceWith(el); } else grid.append(el);
                shown.set(p.id, el);
            });
            grid.querySelector('.empty').hidden = shown.size > 0;
            // waiting: how many are here; running: how many have finished
            const n = d.open ? d.present.filter(p => p.status?.[1] === 'done' || p.status?.[1] === 'locked').length : d.present.length;
            $('present').textContent = n;
            $('total').textContent = d.total;
            $('countLabel').textContent = d.open ? 'peserta selesai' : 'peserta hadir';
            $('title').textContent = d.open ? 'Progres Peserta' : 'Ruang Tunggu Peserta';
            $('bar').style.width = d.total ? Math.min(100, n / d.total * 100) + '%' : '0';
            if (d.open !== open) setOpen(d.open);
        } catch (e) {}
    }

    const sleep = ms => new Promise(r => setTimeout(r, ms));
    $('go').onclick = async () => {
        if (!confirm('Mulai ujian sekarang? Timer semua peserta langsung berjalan.')) return;
        $('go').disabled = true;
        const cd = $('cd');
        cd.classList.add('show');
        for (const n of ['3', '2', '1']) { cd.innerHTML = `<span>${n}</span>`; await sleep(1000); }
        try {
            const res = await fetch(@json(route('admin.open')), {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            });
            if (!res.ok) throw new Error(res.status);
            cd.innerHTML = '<span class="mulai">MULAI!</span>';
            setOpen(true);
            await sleep(1400);
        } catch (e) {
            cd.innerHTML = '<span class="mulai" style="color:#fca5a5;font-size:min(8vw,80px)">Gagal memulai, coba lagi</span>';
            await sleep(2500);
        }
        cd.classList.remove('show');
        $('go').disabled = false;
    };

    $('fs').onclick = () => document.fullscreenElement ? document.exitFullscreen() : document.documentElement.requestFullscreen?.();

    refresh();
    setInterval(refresh, 5000);
})();
</script>
</body>
</html>
