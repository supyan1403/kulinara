<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(BahanSeeder::class);
        $this->call(ResepSeeder::class);
        // Terakhir: akun contoh "sari-pengguna" dilengkapi resep, ulasan,
        // simpanan, dan suka (like) supaya halaman profil publik terlihat hidup.
        $this->call(ProfilSariSeeder::class);
        // Populasi engagement massal biar terlihat sudah banyak yang pakai
        $this->call(PopulasiEngagementSeeder::class);
    }
}
