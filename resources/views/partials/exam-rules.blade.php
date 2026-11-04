<div class="info-row">
    <div class="info-pill"><div class="k">Bagian</div><div class="v">{{ $section->name }}</div></div>
    <div class="info-pill"><div class="k">Jumlah soal</div><div class="v">{{ $section->questions()->count() }}</div></div>
    <div class="info-pill"><div class="k">Waktu</div><div class="v">{{ $section->duration_minutes }} menit</div></div>
</div>
@if($section->directions)<div class="alert alert-info"><strong>Directions:</strong> {{ $section->directions }}</div>@endif
<ul class="rules">
    <li>Jawaban <strong>tersimpan otomatis</strong> setiap kali kamu memilih jawaban.</li>
    <li>Setelah satu bagian selesai, kamu <strong>tidak bisa kembali</strong> ke bagian tersebut.</li>
    <li>Ujian berjalan dalam <strong>layar penuh</strong>. Keluar dari layar penuh atau pindah tab/aplikasi dihitung pelanggaran.</li>
    @if($setting->max_violations > 0)
        <li>Setelah <strong>{{ $setting->max_violations }} pelanggaran</strong>, ujian otomatis diakhiri.</li>
    @endif
</ul>
