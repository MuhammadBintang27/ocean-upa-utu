<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Memuat ulang · OCEAN 2026</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 16px; }
        .busy { max-width: 420px; text-align: center; }
        .spin { width: 44px; height: 44px; margin: 0 auto 18px; border-radius: 50%; border: 4px solid var(--blue-100); border-top-color: var(--blue); animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    {{-- No DB, session or auth here: this page is shown exactly when the database refused a connection. --}}
    <div class="card busy">
        <div class="spin" aria-hidden="true"></div>
        <h2>Server sedang ramai</h2>
        <p class="muted">Jangan tutup halaman ini. Halaman dimuat ulang otomatis dalam beberapa detik, jawaban yang sudah tersimpan tetap aman.</p>
    </div>
    <script>setTimeout(() => location.replace(@json($retry)), 2000 + Math.random() * 3000);</script>
</body>
</html>
