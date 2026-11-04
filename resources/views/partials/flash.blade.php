<div class="toasts" id="toasts" aria-live="polite"></div>
<script>
    window.toast = (message, type = 'ok') => {
        const el = document.createElement('div');
        el.className = 'toast toast-' + type;
        el.setAttribute('role', type === 'err' ? 'alert' : 'status');
        el.innerHTML = '<span class="toast-icon">' + (type === 'err' ? '!' : '✓') + '</span><span class="toast-msg"></span><button type="button" class="toast-x" aria-label="Tutup">×</button>';
        el.querySelector('.toast-msg').textContent = message;
        const close = () => { el.classList.add('hide'); setTimeout(() => el.remove(), 250); };
        el.querySelector('.toast-x').onclick = close;
        document.getElementById('toasts').appendChild(el);
        setTimeout(close, type === 'err' ? 8000 : 4000);
    };
    @if(session('status')) toast(@json(session('status'))); @endif
    @foreach($errors->all() as $e) toast(@json($e), 'err'); @endforeach
</script>
