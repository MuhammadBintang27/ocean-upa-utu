<div id="pagebar" aria-hidden="true"></div>
<div id="busy" role="alertdialog" aria-live="assertive" aria-label="Sedang memproses">
    <div class="busy-box"><span class="spinner spinner-lg"></span><div id="busy-msg">Memproses…</div><div class="muted">Mohon tunggu, jangan tutup halaman ini.</div></div>
</div>
<script>
(() => {
    const bar = document.getElementById('pagebar');
    const busy = document.getElementById('busy');
    window.loading = {
        show(msg = 'Memproses…') { document.getElementById('busy-msg').textContent = msg; busy.classList.add('show'); },
        hide() { busy.classList.remove('show'); },
        bar() { bar.className = 'run'; },
    };
    const markButton = btn => {
        if (!btn || btn.classList.contains('loading')) return;
        btn.dataset.label = btn.innerHTML;
        btn.classList.add('loading');
        btn.setAttribute('aria-busy', 'true');
        btn.innerHTML = '<span class="spinner"></span>' + (btn.dataset.loadingText || 'Memproses…');
    };

    // Downloads are fetched in the background so the overlay can close exactly when the file is ready.
    async function download(url, msg) {
        loading.show(msg || 'Menyiapkan file…');
        try {
            const res = await fetch(url, { credentials: 'same-origin' });
            // An expired session redirects to the login page; don't save that HTML as the file.
            if (!res.ok || res.redirected || (res.headers.get('Content-Type') || '').includes('text/html')) throw new Error(res.status);
            const name = /filename\*?=(?:UTF-8'')?"?([^";]+)"?/i.exec(res.headers.get('Content-Disposition') || '')?.[1] || 'download';
            const a = document.createElement('a');
            a.href = URL.createObjectURL(await res.blob());
            a.download = decodeURIComponent(name);
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(() => URL.revokeObjectURL(a.href), 10000);
            toast('File berhasil dibuat: ' + a.download);
        } catch (e) {
            toast('Gagal membuat file. Coba lagi.', 'err');
        } finally {
            loading.hide();
        }
    }

    document.addEventListener('submit', e => {
        if (e.defaultPrevented) return; // e.g. a confirm() that was cancelled
        const form = e.target;
        if ('download' in form.dataset) {
            e.preventDefault();
            return download(form.action + '?' + new URLSearchParams(new FormData(form)), form.dataset.loading);
        }
        if (form.dataset.busy) return e.preventDefault(); // block double submit
        form.dataset.busy = 1;
        markButton(e.submitter || form.querySelector('button:not([type=button])'));
        if (form.dataset.loading) loading.show(form.dataset.loading);
        loading.bar();
    });

    document.addEventListener('click', e => {
        const a = e.target.closest('a[href]');
        if (!a || e.defaultPrevented || e.ctrlKey || e.metaKey || e.shiftKey || a.target === '_blank') return;
        if ('download' in a.dataset) {
            e.preventDefault();
            return download(a.href, a.dataset.loading);
        }
        if (a.origin === location.origin && !a.hash) loading.bar();
    });

    // Restore buttons when the page comes back from the browser's back/forward cache.
    window.addEventListener('pageshow', e => {
        if (!e.persisted) return;
        bar.className = '';
        loading.hide();
        document.querySelectorAll('form[data-busy]').forEach(f => delete f.dataset.busy);
        document.querySelectorAll('.btn.loading').forEach(b => { b.innerHTML = b.dataset.label; b.classList.remove('loading'); b.removeAttribute('aria-busy'); });
    });
})();
</script>
