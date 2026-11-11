{{--
    Styled replacement for window.confirm().
    JS:    if (await ask({ title, message, ok: 'Mulai', tone: 'green' })) { ... }
    Forms: <form data-confirm="Pesan" data-confirm-title="Judul" data-confirm-ok="Hapus" data-confirm-tone="red">
    Tones: green (start), amber (practice), red (destructive), blue (default).
--}}
<style>
    dialog.ask { border: 0; padding: 0; border-radius: 18px; width: min(460px, calc(100vw - 32px)); background: #fff; color: var(--ink); box-shadow: 0 30px 80px rgba(16, 11, 48, .45); }
    dialog.ask[open] { animation: ask-in .18s ease-out; }
    dialog.ask::backdrop { background: rgba(16, 11, 48, .62); backdrop-filter: blur(3px); }
    @keyframes ask-in { from { opacity: 0; transform: translateY(8px) scale(.97); } }
    .ask-body { display: flex; gap: 16px; padding: 26px 26px 6px; }
    .ask-icon { width: 46px; height: 46px; flex-shrink: 0; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: var(--tone-bg); color: var(--tone); }
    .ask-icon svg { width: 22px; height: 22px; fill: none; stroke: currentColor; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; }
    .ask h3 { margin: 4px 0 6px; color: var(--navy); font-size: 18px; line-height: 1.3; }
    .ask p { margin: 0; color: #4b5563; font-size: 15px; line-height: 1.55; white-space: pre-line; }
    .ask-actions { display: flex; justify-content: flex-end; gap: 10px; padding: 22px 26px 24px; }
    .ask-actions button { border: 0; border-radius: 10px; padding: 11px 20px; font: inherit; font-weight: 700; font-size: 15px; cursor: pointer; }
    .ask-actions .ask-cancel { background: #eef0f5; color: #374151; }
    .ask-actions .ask-cancel:hover { background: #e2e5ee; }
    .ask-actions .ask-ok { background: var(--tone); color: #fff; box-shadow: 0 3px 0 var(--tone-dark); }
    .ask-actions .ask-ok:hover { filter: brightness(1.06); }
    .ask-actions button:focus-visible { outline: 3px solid var(--tone-bg); outline-offset: 2px; }
    dialog.ask[data-tone="green"] { --tone: #16a34a; --tone-dark: #15803d; --tone-bg: #dcfce7; }
    dialog.ask[data-tone="amber"] { --tone: #d97706; --tone-dark: #b45309; --tone-bg: #fef3c7; }
    dialog.ask[data-tone="red"]   { --tone: #dc2626; --tone-dark: #b91c1c; --tone-bg: #fee2e2; }
    dialog.ask[data-tone="blue"]  { --tone: var(--blue); --tone-dark: var(--blue-700); --tone-bg: var(--blue-100); }
</style>

<dialog class="ask" id="ask" aria-labelledby="ask-title" aria-describedby="ask-message">
    <div class="ask-body">
        <div class="ask-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" data-icon="green"><path d="M8 5l11 7-11 7z"/></svg>
            <svg viewBox="0 0 24 24" data-icon="amber"><path d="M3 12a9 9 0 1 0 3-6.7M3 4v5h5"/></svg>
            <svg viewBox="0 0 24 24" data-icon="red"><path d="M12 9v4M12 17h.01M10.3 3.9L2.4 17.5A2 2 0 0 0 4.1 20.5h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
            <svg viewBox="0 0 24 24" data-icon="blue"><path d="M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20zM12 16v-4M12 8h.01"/></svg>
        </div>
        <div>
            <h3 id="ask-title"></h3>
            <p id="ask-message"></p>
        </div>
    </div>
    <div class="ask-actions">
        <button type="button" class="ask-cancel" data-answer="">Batal</button>
        <button type="button" class="ask-ok" data-answer="ok"></button>
    </div>
</dialog>

<script>
(() => {
    const d = document.getElementById('ask');
    let resolver = null;

    // plain buttons (not <form method="dialog">) so the page's submit/loading handlers never see them
    d.querySelectorAll('[data-answer]').forEach(b => b.addEventListener('click', () => d.close(b.dataset.answer)));
    d.addEventListener('close', () => { resolver?.(d.returnValue === 'ok'); resolver = null; });
    d.addEventListener('click', e => { if (e.target === d) d.close(''); }); // click on the backdrop cancels

    window.ask = ({ title = 'Lanjutkan?', message = '', ok = 'Ya, lanjutkan', tone = 'blue' } = {}) => new Promise(resolve => {
        resolver = resolve;
        d.dataset.tone = tone;
        d.querySelectorAll('[data-icon]').forEach(i => i.style.display = i.dataset.icon === tone ? '' : 'none');
        d.querySelector('#ask-title').textContent = title;
        d.querySelector('#ask-message').textContent = message;
        d.querySelector('.ask-ok').textContent = ok;
        d.returnValue = '';
        d.showModal();
        // destructive actions focus "Batal" so Enter doesn't delete by accident
        d.querySelector(tone === 'red' ? '.ask-cancel' : '.ask-ok').focus();
    });

    // capture phase: runs before the loading/double-submit handler, which then sees defaultPrevented
    document.addEventListener('submit', async e => {
        const form = e.target;
        if (!form.dataset.confirm || form.dataset.confirmed) return;
        e.preventDefault();
        const submitter = e.submitter;
        const yes = await ask({
            title: form.dataset.confirmTitle || 'Yakin?',
            message: form.dataset.confirm,
            ok: form.dataset.confirmOk || 'Ya, lanjutkan',
            tone: form.dataset.confirmTone || 'red',
        });
        if (yes) {
            form.dataset.confirmed = '1';
            form.requestSubmit(submitter || undefined);
        }
    }, true);
})();
</script>
