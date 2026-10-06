<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Langkah 6 (versi seeder, setara perintah Tinker di modul):
     * membuat akun admin default bila belum ada.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@unsoed.ac.id'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('rahasia123'),
                'peran' => 'admin',
            ]
        );
    }
}
