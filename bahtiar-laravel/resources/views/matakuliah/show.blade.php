@extends('layouts.app')

@section('judul', 'Detail Mata Kuliah')

@section('konten')
<h1 class="h3 mb-4">Detail Mata Kuliah</h1>

<div class="card">
    <div class="card-header font-weight-bold">
        {{ $matakuliah['kode'] }} - {{ $matakuliah['nama'] }}
    </div>
    <div class="card-body">
        <p class="mb-2"><strong>Kode:</strong> {{ $matakuliah['kode'] }}</p>
        <p class="mb-2"><strong>Nama Mata Kuliah:</strong> {{ $matakuliah['nama'] }}</p>
        <p class="mb-0"><strong>Jumlah SKS:</strong> <x-badge-sks :sks="$matakuliah['sks']" /></p>
    </div>
</div>

<a href="{{ route('matakuliah.index') }}" class="btn btn-secondary mt-3">Kembali</a>
@endsection