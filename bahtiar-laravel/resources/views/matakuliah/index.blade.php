@extends('layouts.app')

@section('judul', 'Daftar Mata Kuliah')

@section('konten')
<h1 class="h3 mb-4">Daftar Mata Kuliah</h1>

<form action="{{ route('matakuliah.index') }}" method="GET" class="mb-4">
    <div class="input-group">
        <input type="text" name="q" class="form-control" placeholder="Cari kode atau nama mata kuliah..." value="{{ $kataKunci }}">
        <button class="btn btn-primary" type="submit">Cari</button>
        @if($kataKunci)
            <a href="{{ route('matakuliah.index') }}" class="btn btn-outline-secondary">Reset</a>
        @endif
    </div>
</form>

<table class="table table-bordered bg-white">
    <thead class="table-dark">
        <tr>
            <th>Kode</th>
            <th>Nama Mata Kuliah</th>
            <th>SKS</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($daftarMatakuliah as $item)
        <tr>
            <td>{{ $item['kode'] }}</td>
            <td>{{ $item['nama'] }}</td>
            <td><x-badge-sks :sks="$item['sks']" /></td>
            <td>
                <a href="{{ route('matakuliah.show', $item['kode']) }}" class="btn btn-sm btn-info text-white">Detail</a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="4" class="text-center">Data mata kuliah tidak ditemukan.</td>
        </tr>
        @endforelse
    </tbody>
</table>
@endsection