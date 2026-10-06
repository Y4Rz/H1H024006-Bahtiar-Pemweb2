<?php

use App\Http\Controllers\Api\AuthController;
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

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/profil', [AuthController::class, 'profil']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout-semua', [AuthController::class, 'logoutSemua']);
    Route::put('/auth/password', [AuthController::class, 'ubahPassword']);

    Route::get('/mahasiswa', [MahasiswaController::class, 'index']);
    Route::get('/mahasiswa/{mahasiswa}', [MahasiswaController::class, 'show']);

    Route::middleware('ability:mahasiswa:tulis')->group(function () {
        Route::post('/mahasiswa', [MahasiswaController::class, 'store']);
        Route::put('/mahasiswa/{mahasiswa}', [MahasiswaController::class, 'update']);
        Route::patch('/mahasiswa/{mahasiswa}', [MahasiswaController::class, 'update']);
    });

    // Tugas 2: hapus mahasiswa hanya untuk admin.
    Route::delete('/mahasiswa/{mahasiswa}', [MahasiswaController::class, 'destroy'])
        ->middleware(['ability:mahasiswa:tulis', 'peran.admin']);

    Route::get('/matakuliah', [MatakuliahController::class, 'index']);
    Route::get('/matakuliah/{matakuliah}', [MatakuliahController::class, 'show']);

    Route::middleware('ability:mahasiswa:tulis')->group(function () {
        Route::post('/matakuliah', [MatakuliahController::class, 'store']);
        Route::put('/matakuliah/{matakuliah}', [MatakuliahController::class, 'update']);
        Route::patch('/matakuliah/{matakuliah}', [MatakuliahController::class, 'update']);
        Route::delete('/matakuliah/{matakuliah}', [MatakuliahController::class, 'destroy']);
    });

    Route::get('program-studi', [ProgramStudiController::class, 'index']);
    Route::get('program-studi/{program_studi}', [ProgramStudiController::class, 'show']);
    Route::get('program-studi/{program_studi}/mahasiswa', [ProgramStudiController::class, 'mahasiswa']);
});
