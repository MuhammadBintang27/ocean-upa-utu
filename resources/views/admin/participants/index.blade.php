@extends('layouts.admin')

@section('title', 'Peserta')

@section('actions')
    <form method="get" action="{{ route('admin.participants.cards') }}" style="display:flex;gap:6px;align-items:center" data-download data-loading="Membuat kartu peserta (PDF)…">
        <select name="per_page" aria-label="Kartu per halaman" style="width:auto;padding:5px 8px;font-size:13px">
            <option value="4">4 kartu / A4</option>
            <option value="6">6 kartu / A4</option>
        </select>
        <button class="btn btn-sm btn-navy">🪪 Cetak kartu (PDF)</button>
    </form>
    <a class="btn btn-sm" href="{{ route('admin.participants.accounts') }}" data-download data-loading="Membuat daftar akun (Excel)…">⬇ Daftar akun (Excel)</a>
@endsection

@push('head')
<style>
    .pw { font-family: ui-monospace, Consolas, monospace; background: var(--blue-50); padding: 2px 8px; border-radius: 6px; cursor: pointer; filter: blur(4px); transition: filter .15s; user-select: none; }
    .pw.show { filter: none; user-select: text; }
    .drop { border: 2px dashed var(--blue-100); border-radius: 12px; padding: 18px; text-align: center; background: var(--blue-50); }
    .drop input { margin-top: 8px; }
</style>
@endpush

@section('content')
<div class="grid-2">
    <form class="card" method="post" action="{{ route('admin.participants.import') }}" enctype="multipart/form-data" data-loading="Mengimport peserta & membuat akun…">
        @csrf
        <h3>Import dari Excel</h3>
        <p class="muted" style="margin-top:0">Kolom A = <strong>Nama</strong>, kolom B = <strong>Asal Sekolah</strong>. Username & password dibuat otomatis oleh sistem.</p>
        <div class="drop">
            <input type="file" name="file" accept=".xlsx,.xls,.csv" required>
            <div class="muted" style="margin-top:6px">Format .xlsx, .xls, atau .csv · maks. 5 MB</div>
        </div>
        <div style="display:flex;gap:8px;margin-top:16px;flex-wrap:wrap">
            <button class="btn">Import peserta</button>
            <a class="btn btn-gray" href="{{ route('admin.participants.template') }}" data-download data-loading="Menyiapkan template…">Download template</a>
        </div>
    </form>

    <form class="card" method="post" action="{{ route('admin.participants.store') }}">
        @csrf
        <h3>Tambah satu peserta</h3>
        <label>Nama</label><input type="text" name="name" required>
        <label>Asal sekolah</label><input type="text" name="school">
        <p class="muted">Username & password dibuat otomatis.</p>
        <button class="btn">Tambah peserta</button>
    </form>
</div>

<div class="card">
    <div class="card-head">
        <h3>Daftar peserta</h3><span class="badge badge-blue">{{ $participants->count() }}</span>
        <span class="spacer"></span>
        <span class="muted">Klik password untuk menampilkan</span>
    </div>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Username</th><th>Password</th><th>Nama</th><th>Asal sekolah</th><th>Perangkat</th><th></th></tr></thead>
        <tbody>
        @forelse($participants as $p)
            <tr>
                <td><strong>{{ $p->username }}</strong></td>
                <td>@if($p->plain_password)<span class="pw" onclick="this.classList.toggle('show')">{{ $p->plain_password }}</span>@else<span class="muted">-</span>@endif</td>
                <td>@if($p->avatarUrl())<img src="{{ $p->avatarUrl() }}" alt="" width="24" height="24" style="vertical-align:-7px;margin-right:8px;border-radius:50%">@endif{{ $p->name }}</td>
                <td>{{ $p->school }}</td>
                <td>@if($p->device_token)<span class="badge badge-yellow">terkunci</span>@else<span class="badge">bebas</span>@endif</td>
                <td class="actions">
                    <a class="btn btn-sm btn-gray" href="{{ route('admin.participants.show', $p) }}">Detail</a>
                    <a class="btn btn-sm btn-gray" href="{{ route('admin.participants.edit', $p) }}">Edit</a>
                    <form class="inline" method="post" action="{{ route('admin.participants.reset-device', $p) }}">@csrf<button class="btn btn-sm btn-gray">Reset perangkat</button></form>
                    <form class="inline" method="post" action="{{ route('admin.participants.destroy', $p) }}" onsubmit="return confirm('Hapus peserta ini beserta semua jawabannya?')">@csrf @method('DELETE')<button class="btn btn-sm btn-red">Hapus</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">Belum ada peserta. Import dari Excel atau tambah satu per satu.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection
