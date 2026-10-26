<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Akun staf pertama. Staf tidak mendaftar sendiri, akun dibuat admin
     * (lihat docs/DECISIONS.md). Semua akun demo memakai password `password`.
     */
    public function run(): void
    {
        $akun = [
            ['email' => 'admin@klinik.test', 'name' => 'Admin Klinik', 'role' => 'admin'],
            ['email' => 'petugas@klinik.test', 'name' => 'Petugas Pendaftaran', 'role' => 'petugas'],
            ['email' => 'apoteker@klinik.test', 'name' => 'Apoteker Klinik', 'role' => 'apoteker'],
        ];

        foreach ($akun as $data) {
            User::query()->updateOrCreate(
                ['email' => $data['email']],
                [...$data, 'password' => 'password', 'is_active' => true],
            );

            $this->command?->info("Akun {$data['role']} siap: {$data['email']} (password: password)");
        }
    }
}
