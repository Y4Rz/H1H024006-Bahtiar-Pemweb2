<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Matakuliah;
use App\Models\Mahasiswa;

class MatakuliahSeeder extends Seeder
{
    public function run(): void
    {
        $mk = [
            ['kode' => 'TKO101', 'nama' => 'Pemrograman Web II', 'sks' => 3, 'semester' => 4],
            ['kode' => 'TKO102', 'nama' => 'Struktur Data', 'sks' => 3, 'semester' => 2],
            ['kode' => 'TKO103', 'nama' => 'Sistem Operasi', 'sks' => 3, 'semester' => 3],
            ['kode' => 'TKO104', 'nama' => 'Jaringan Komputer', 'sks' => 3, 'semester' => 4],
        ];

        foreach ($mk as $item) {
            Matakuliah::create($item);
        }

        // Lampirkan mata kuliah acak beserta nilai ke semua mahasiswa
        $allMk = Matakuliah::all();
        $nilaiArr = ['A', 'AB', 'B', 'BC', 'C'];

        Mahasiswa::all()->each(function ($mhs) use ($allMk, $nilaiArr) {
            $randomMk = $allMk->random(rand(2, 4));
            foreach ($randomMk as $m) {
                $mhs->matakuliah()->attach($m->id, [
                    'nilai' => $nilaiArr[array_rand($nilaiArr)]
                ]);
            }
        });
    }
}