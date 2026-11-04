@extends('layouts.admin')

@section('title', $question->exists ? 'Edit Soal' : 'Tambah Soal')

@section('content')
<form class="card" method="post" action="{{ $question->exists ? route('admin.questions.update', $question) : route('admin.questions.store') }}" style="max-width:860px">
    @csrf
    @if($question->exists) @method('PUT') @endif

    <div class="grid">
        <div>
            <label>Bagian</label>
            <select name="section_id" required>
                @foreach($sections as $s)<option value="{{ $s->id }}" @selected(old('section_id', $question->section_id) == $s->id)>{{ $s->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label>Passage <span class="muted">(opsional)</span></label>
            <select name="passage_id">
                <option value="">— tanpa passage —</option>
                @foreach($passages as $p)<option value="{{ $p->id }}" @selected(old('passage_id', $question->passage_id) == $p->id)>{{ $p->title }}</option>@endforeach
            </select>
        </div>
        <div><label>Urutan</label><input type="number" name="sort" min="0" value="{{ old('sort', $question->sort) }}" required></div>
    </div>

    <label>Pertanyaan</label>
    <textarea name="text" rows="3" required>{{ old('text', $question->text) }}</textarea>

    <div class="grid-2" style="gap:0 18px">
        @foreach(\App\Models\Question::CHOICES as $c)
            <div>
                <label>Opsi {{ strtoupper($c) }}</label>
                <input type="text" name="option_{{ $c }}" value="{{ old('option_'.$c, $question->{'option_'.$c}) }}" required>
            </div>
        @endforeach
    </div>

    <label>Kunci jawaban</label>
    <select name="answer" required style="max-width:140px">
        @foreach(\App\Models\Question::CHOICES as $c)<option value="{{ $c }}" @selected(old('answer', $question->answer) === $c)>{{ strtoupper($c) }}</option>@endforeach
    </select>

    <div style="margin-top:20px;display:flex;gap:8px">
        <button class="btn">Simpan soal</button>
        <a class="btn btn-gray" href="{{ route('admin.questions.index') }}">Batal</a>
    </div>
</form>
@endsection
