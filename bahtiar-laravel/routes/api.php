<?php

use App\Http\Controllers\Api\MahasiswaController;
use App\Http\Controllers\Api\MatakuliahController;
use App\Http\Controllers\Api\ProgramStudiController;
use Illuminate\Support\Facades\Route;

Route::get('/status', function () {
    return response()->json([
        'sukses' => true,
        'pesan' => 'API Pemweb II aktif',
        'waktu' => now()->toIso8601String(),
    ]);
});

Route::apiResource('mahasiswa', MahasiswaController::class);

Route::apiResource('matakuliah', MatakuliahController::class);

Route::get('program-studi', [ProgramStudiController::class, 'index']);
Route::get('program-studi/{program_studi}', [ProgramStudiController::class, 'show']);
Route::get('program-studi/{program_studi}/mahasiswa', [ProgramStudiController::class, 'mahasiswa']);
