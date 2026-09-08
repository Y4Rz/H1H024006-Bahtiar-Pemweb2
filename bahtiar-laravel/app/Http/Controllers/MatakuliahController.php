<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MatakuliahController extends Controller
{

    private array $daftarMatakuliah = [
        ['kode' => 'TKO101', 'nama' => 'Pemrograman Web II', 'sks' => 3],
        ['kode' => 'TKO102', 'nama' => 'Struktur Data', 'sks' => 3],
        ['kode' => 'TKO103', 'nama' => 'Bahasa Inggris', 'sks' => 2],
        ['kode' => 'TKO104', 'nama' => 'Sistem Operasi', 'sks' => 3],
        ['kode' => 'TKO105', 'nama' => 'Pancasila', 'sks' => 2],
    ];

    public function index(Request $request)
    {
        $q = $request->query('q', '');
        $matakuliah = $this->daftarMatakuliah;

        if ($q !== '') {
            $matakuliah = array_filter($matakuliah, function ($item) use ($q) {
                return stripos($item['nama'], $q) !== false || stripos($item['kode'], $q) !== false;
            });
        }

        return view('matakuliah.index', [
            'daftarMatakuliah' => $matakuliah,
            'kataKunci'        => $q,
        ]);
    }

    public function show(string $kode)
    {
        $matakuliah = collect($this->daftarMatakuliah)->firstWhere('kode', $kode);

        if (!$matakuliah) {
            abort(404, 'Mata kuliah tidak ditemukan');
        }

        return view('matakuliah.show', ['matakuliah' => $matakuliah]);
    }
}