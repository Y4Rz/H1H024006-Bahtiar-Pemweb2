<?php

use Illuminate\Support\Facades\Route;

// Tugas 2: Tampilkan Nama dan NIM pada halaman depan Laravel
Route::get('/', function () {
    return response()->json([
        'status'  => 'Sukses',
        'pesan'   => 'Selamat Datang di Praktikum Pemrograman Web II',
        'nama'    => 'Bahtiar Rizqi Efendy',       // Sesuaikan dengan nama lengkap Anda
        'nim'     => 'H1H024006',     // Sesuaikan dengan NIM Anda
    ]);
});