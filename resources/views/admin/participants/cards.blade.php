{{-- Rendered by dompdf: tables instead of flex/grid, sizes in mm. --}}
@php
    $six = $perPage === 6;
    // dompdf adds cell padding on top of the height, so this is the row height minus 5mm padding.
    // Rows: 2 x 138mm or 3 x 92mm = 276mm, inside the 281mm printable A4 height.
    $cellHeight = $six ? 87 : 133;
@endphp
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { size: A4 portrait; margin: 8mm; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: "DejaVu Sans", sans-serif; color: #111827; }
    .page { width: 100%; table-layout: fixed; border-collapse: collapse; page-break-after: always; }
    .page:last-child { page-break-after: auto; }
    .cell { width: 50%; height: {{ $cellHeight }}mm; padding: 2.5mm; vertical-align: top; border: 0.3mm dashed #b8c0d4; }
    .card { height: {{ $cellHeight - 1 }}mm; border: 0.5mm solid #241b5e; border-radius: 3.5mm; overflow: hidden; position: relative; }
    .head { background: #241b5e; padding: {{ $six ? '2.5mm 4mm' : '4mm 5mm' }}; color: #fff; }
    .head table { width: 100%; border-collapse: collapse; }
    .head img { height: 12mm; }
    .title { text-align: right; vertical-align: middle; white-space: nowrap; }
    .title b { display: block; font-size: {{ $six ? 11 : 12 }}pt; letter-spacing: .6pt; }
    .steps { margin-top: 4mm; font-size: 7.5pt; color: #374151; }
    .steps b { color: #241b5e; font-size: 7pt; text-transform: uppercase; letter-spacing: .5pt; }
    .steps ol { margin: 1.5mm 0 0; padding-left: 4.5mm; }
    .steps li { margin-bottom: .8mm; }
    .sign { margin-top: 3mm; width: 100%; border-collapse: collapse; }
    .sign td { width: 50%; font-size: 6.5pt; color: #6b7280; vertical-align: top; }
    .sign .box { height: 11mm; border: 0.3mm solid #d5dbe8; border-radius: 1.5mm; margin-top: 1mm; }
    .title span { font-size: {{ $six ? 5.5 : 6.5 }}pt; color: #cdd6ff; }
    .stripe { height: 1.2mm; background: #d42027; }
    .body { padding: {{ $six ? '2.5mm 4mm 0' : '5mm 5mm 0' }}; }
    .label { font-size: {{ $six ? 6 : 7 }}pt; color: #6b7280; text-transform: uppercase; letter-spacing: .5pt; }
    .value { font-size: {{ $six ? 9.5 : 12 }}pt; font-weight: bold; color: #241b5e; margin-bottom: {{ $six ? 1.5 : 3 }}mm; }
    .school { font-size: {{ $six ? 8 : 10 }}pt; font-weight: normal; color: #111827; }
    .creds { width: 100%; border-collapse: separate; border-spacing: {{ $six ? '1.5mm 0' : '2mm 0' }}; margin: 0 -{{ $six ? 1.5 : 2 }}mm; }
    .cred { background: #eef2fb; border-radius: 2mm; padding: {{ $six ? '1.8mm 2.5mm' : '3mm 3.5mm' }}; width: 50%; }
    .cred .v { font-family: "DejaVu Sans Mono", monospace; font-size: {{ $six ? 11 : 14 }}pt; font-weight: bold; color: #241b5e; }
    .foot { position: absolute; left: 0; right: 0; bottom: 0; padding: {{ $six ? '1.5mm 4mm' : '3mm 5mm' }}; border-top: 0.3mm solid #e5e9f2; }
    .foot table { width: 100%; border-collapse: collapse; }
    .foot img { height: {{ $six ? 6 : 8.5 }}mm; }
    .foot .note { font-size: {{ $six ? 5.5 : 6.5 }}pt; color: #6b7280; text-align: right; vertical-align: middle; }</style>
</head>
<body>
@forelse($participants->chunk($perPage) as $page)
    <table class="page">
        @foreach($page->values()->chunk(2) as $pair)
            <tr>
                @foreach($pair as $p)
                    <td class="cell">
                        <div class="card">
                            <div class="head">
                                <table><tr>
                                    <td><img src="{{ public_path('image/ocean-wordmark.png') }}" alt=""></td>
                                    <td class="title"><b>KARTU PESERTA</b><span>Olympiad of Competency in<br>English, Agro, and Marine</span></td>
                                </tr></table>
                            </div>
                            <div class="stripe"></div>
                            <div class="body">
                                <div class="label">Nama peserta</div>
                                <div class="value">{{ $p->name }}</div>
                                <div class="label">Asal sekolah</div>
                                <div class="value school">{{ $p->school ?: '-' }}</div>
                                <table class="creds"><tr>
                                    <td class="cred"><div class="label">Username</div><div class="v">{{ $p->username }}</div></td>
                                    <td class="cred"><div class="label">Password</div><div class="v">{{ $p->plain_password ?? '-' }}</div></td>
                                </tr></table>
                                @unless($six)
                                    <div class="steps">
                                        <b>Petunjuk</b>
                                        <ol>
                                            <li>Buka alamat ujian yang diberikan pengawas.</li>
                                            <li>Masuk dengan username &amp; password di atas.</li>
                                            <li>Tunggu di ruang tunggu, ujian dimulai serentak.</li>
                                        </ol>
                                    </div>
                                    <table class="sign"><tr>
                                        <td>No. meja<div class="box"></div></td>
                                        <td style="padding-left:2mm">Paraf pengawas<div class="box"></div></td>
                                    </tr></table>
                                @endunless
                            </div>
                            <div class="foot">
                                <table><tr>
                                    <td><img src="{{ public_path('image/ocean-upa.png') }}" alt=""></td>
                                    <td class="note">Bawa kartu ini saat ujian.<br>Jangan berikan password ke orang lain.</td>
                                </tr></table>
                            </div>
                        </div>
                    </td>
                @endforeach
                @if($pair->count() === 1)<td class="cell" style="border:0"></td>@endif
            </tr>
        @endforeach
    </table>
@empty
    <p>Belum ada peserta.</p>
@endforelse
</body>
</html>
