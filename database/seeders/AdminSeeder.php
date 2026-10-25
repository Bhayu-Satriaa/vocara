<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Akun admin pertama. Staf tidak mendaftar sendiri, akun dibuat admin
     * (lihat docs/DECISIONS.md).
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@klinik.test'],
            [
                'name' => 'Admin Klinik',
                'password' => 'password', // password awal untuk demo, ganti setelah login
                'role' => 'admin',
                'is_active' => true,
            ],
        );

        $this->command?->info("Akun admin siap: {$admin->email} (password: password)");
    }
}
