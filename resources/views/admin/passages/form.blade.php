@extends('layouts.admin')

@section('title', $passage->exists ? 'Edit Passage' : 'Tambah Passage')

@section('content')
<form class="card" method="post" action="{{ $passage->exists ? route('admin.passages.update', $passage) : route('admin.passages.store') }}" style="max-width:860px">
    @csrf
    @if($passage->exists) @method('PUT') @endif

    <div class="grid">
        <div>
            <label>Bagian</label>
            <select name="section_id" required>
                @foreach($sections as $s)<option value="{{ $s->id }}" @selected(old('section_id', $passage->section_id) == $s->id)>{{ $s->name }}</option>@endforeach
            </select>
        </div>
        <div><label>Urutan</label><input type="number" name="sort" min="0" value="{{ old('sort', $passage->sort ?? 0) }}" required></div>
    </div>

    <label>Judul</label>
    <input type="text" name="title" value="{{ old('title', $passage->title) }}" required>
    <label>Isi teks <span class="muted">(pisahkan paragraf dengan baris kosong)</span></label>
    <textarea name="body" rows="14" required>{{ old('body', $passage->body) }}</textarea>
    <div class="alert alert-info" style="margin-top:14px">Passage tampil di atas soal pertama yang memakai passage ini. Pilih passage di form soal.</div>

    <div style="display:flex;gap:8px">
        <button class="btn">Simpan passage</button>
        <a class="btn btn-gray" href="{{ route('admin.questions.index') }}">Batal</a>
    </div>
</form>
@endsection
