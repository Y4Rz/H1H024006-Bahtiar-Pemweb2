<?php

namespace Database\Seeders;

use App\Models\Mahasiswa;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ProgramStudiSeeder::class);

        Mahasiswa::factory()->count(30)->create();

        $this->call(MatakuliahSeeder::class);
    }
}
