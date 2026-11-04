@extends('layouts.admin')

@section('title', 'Edit Peserta')

@section('content')
<form class="card" method="post" action="{{ route('admin.participants.update', $participant) }}" style="max-width:560px">
    @csrf @method('PUT')
    <label>Username</label><input type="text" name="username" value="{{ old('username', $participant->username) }}" required>
    <label>Nama</label><input type="text" name="name" value="{{ old('name', $participant->name) }}" required>
    <label>Sekolah</label><input type="text" name="school" value="{{ old('school', $participant->school) }}">
    <label>Password baru <span class="muted">(kosongkan jika tidak diganti)</span></label><input type="text" name="password">
    @if($participant->plain_password)<p class="muted">Password saat ini: <code>{{ $participant->plain_password }}</code></p>@endif
    <div style="margin-top:18px;display:flex;gap:8px">
        <button class="btn">Simpan</button>
        <a class="btn btn-gray" href="{{ route('admin.participants.index') }}">Batal</a>
    </div>
</form>
@endsection
