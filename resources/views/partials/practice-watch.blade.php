{{-- Practice round only: move to the real exam as soon as the committee starts it, or restart the practice
     round when the committee repeats it (DB-free status poll). --}}
<script>
    setInterval(async () => {
        try {
            const res = await fetch(@json(route('exam.status')), { headers: { Accept: 'application/json' } });
            const d = res.ok ? await res.json() : {};
            if (d.open || (d.practice && d.round !== @json((int) $setting->practice_round))) {
                window.loading?.show(d.open ? 'Ujian dimulai! Menyiapkan soal…' : 'Simulasi diulang! Menyiapkan soal…');
                setTimeout(() => location.replace(@json(route('exam'))), Math.random() * 6000);
            }
        } catch (e) {}
    }, 5000);
</script>
