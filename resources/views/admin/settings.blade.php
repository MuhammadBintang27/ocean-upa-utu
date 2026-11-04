@extends('layouts.admin')

@section('title', 'Pengaturan')

@section('content')
<form method="post" action="{{ route('admin.settings.update') }}">
    @csrf @method('PUT')
    <div class="card">
        <h3>Pengaturan umum</h3>
        <label class="check"><input type="hidden" name="is_open" value="0"><input type="checkbox" name="is_open" value="1" @checked($setting->is_open)> Ujian dibuka (saat dicentang, semua peserta di ruang tunggu mulai serentak)</label>
        <label for="max_violations">Maks. pelanggaran sebelum ujian otomatis diakhiri <span class="muted">(0 = tidak dibatasi)</span></label>
        <input type="number" id="max_violations" name="max_violations" min="0" value="{{ old('max_violations', $setting->max_violations) }}" style="max-width:160px">
        <label class="check"><input type="hidden" name="shuffle_questions" value="0"><input type="checkbox" name="shuffle_questions" value="1" @checked($setting->shuffle_questions)> Acak urutan soal per peserta <span class="muted">(soal satu passage tetap berurutan; urutan tiap peserta tetap sama walau halaman di-reload)</span></label>
    </div>

    @foreach($sections as $s)
        <div class="card">
            <div class="card-head"><span class="badge badge-blue">Bagian {{ $loop->iteration }}</span><h3>{{ $s->name }}</h3></div>
            <div class="grid">
                <div><label>Nama bagian</label><input type="text" name="sections[{{ $s->id }}][name]" value="{{ $s->name }}"></div>
                <div><label>Urutan</label><input type="number" name="sections[{{ $s->id }}][sort]" min="0" value="{{ $s->sort }}"></div>
                <div><label>Durasi (menit)</label><input type="number" name="sections[{{ $s->id }}][duration_minutes]" min="1" value="{{ $s->duration_minutes }}"></div>
                <div><label>Poin benar</label><input type="number" step="0.01" name="sections[{{ $s->id }}][correct_points]" value="{{ $s->correct_points + 0 }}"></div>
                <div><label>Poin salah</label><input type="number" step="0.01" name="sections[{{ $s->id }}][wrong_points]" value="{{ $s->wrong_points + 0 }}"></div>
                <div><label>Poin kosong</label><input type="number" step="0.01" name="sections[{{ $s->id }}][blank_points]" value="{{ $s->blank_points + 0 }}"></div>
            </div>
            <label>Directions (petunjuk untuk peserta)</label>
            <textarea name="sections[{{ $s->id }}][directions]" rows="3">{{ $s->directions }}</textarea>
        </div>
    @endforeach

    <div class="alert alert-info">Perubahan durasi berlaku langsung, termasuk untuk peserta yang sedang mengerjakan. Perubahan poin langsung menghitung ulang semua nilai.</div>
    <button class="btn btn-lg">Simpan pengaturan</button>
</form>
@endsection
