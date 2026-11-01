<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    /**
     * Buat satu user contoh untuk tiap role.
     * Semua password: "password"
     *
     * firstOrCreate supaya seeding bisa dijalankan ulang tanpa gagal
     * karena pelanggaran unique constraint di kolom email.
     */
    public function run(): void
    {
        $users = [
            ['name' => 'Sari Pengguna', 'email' => 'pengguna@kulinara.test', 'role' => UserRole::Pengguna],
            ['name' => 'Andi Pengelola', 'email' => 'pengelola@kulinara.test', 'role' => UserRole::Pengelola],
        ];

        foreach ($users as $user) {
            User::query()->firstOrCreate(
                ['email' => $user['email']],
                [
                    ...$user,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
