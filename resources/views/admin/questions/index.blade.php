@extends('layouts.admin')

@section('title', 'Bank Soal')

@section('content')
@foreach($sections as $section)
    <div class="card">
        <div class="card-head">
            <span class="badge badge-blue">Bagian {{ $loop->iteration }}</span>
            <h2>{{ $section->name }}</h2>
            <span class="muted">{{ $section->questions->count() }} soal · {{ $section->duration_minutes }} menit</span>
            <span class="spacer"></span>
            <a class="btn btn-sm" href="{{ route('admin.questions.create', ['section_id' => $section->id]) }}">+ Soal</a>
            <a class="btn btn-sm btn-navy" href="{{ route('admin.passages.create', ['section_id' => $section->id]) }}">+ Passage</a>
        </div>

        @if($section->passages->isNotEmpty())
            <h4>Passage</h4>
            <div class="table-wrap" style="margin-bottom:18px">
            <table>
                <thead><tr><th style="width:60px">Urut</th><th>Judul & isi</th><th></th></tr></thead>
                @foreach($section->passages as $p)
                    <tr>
                        <td>{{ $p->sort }}</td>
                        <td><strong>{{ $p->title }}</strong><div class="muted">{{ \Illuminate\Support\Str::limit($p->body, 160) }}</div></td>
                        <td class="actions">
                            <a class="btn btn-sm btn-gray" href="{{ route('admin.passages.edit', $p) }}">Edit</a>
                            <form class="inline" method="post" action="{{ route('admin.passages.destroy', $p) }}" onsubmit="return confirm('Hapus passage ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-red">Hapus</button></form>
                        </td>
                    </tr>
                @endforeach
            </table>
            </div>
        @endif

        <h4>Soal</h4>
        <div class="table-wrap">
        <table>
            <thead><tr><th style="width:60px">No</th><th>Soal & opsi</th><th>Passage</th><th>Kunci</th><th></th></tr></thead>
            <tbody>
            @forelse($section->questions as $i => $q)
                <tr>
                    <td><strong>{{ $i + 1 }}</strong></td>
                    <td>{{ $q->text }}
                        <div class="muted">
                            @foreach(\App\Models\Question::CHOICES as $c)
                                <span @if($q->answer === $c) style="color:var(--green);font-weight:700" @endif>{{ strtoupper($c) }}. {{ $q->{'option_'.$c} }}</span>@if(! $loop->last) · @endif
                            @endforeach
                        </div>
                    </td>
                    <td class="muted">{{ $q->passage?->title }}</td>
                    <td><span class="badge badge-green">{{ strtoupper($q->answer) }}</span></td>
                    <td class="actions">
                        <a class="btn btn-sm btn-gray" href="{{ route('admin.questions.edit', $q) }}">Edit</a>
                        <form class="inline" method="post" action="{{ route('admin.questions.destroy', $q) }}" onsubmit="return confirm('Hapus soal ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-red">Hapus</button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Belum ada soal.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>
@endforeach
@endsection
