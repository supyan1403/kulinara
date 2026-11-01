<?php

namespace Database\Seeders;

use App\Models\Resep;
use App\Models\Simpanan;
use App\Models\Suka;
use App\Models\User;
use Illuminate\Database\Seeder;

class PopulasiEngagementSeeder extends Seeder
{
    public function run(): void
    {
        $semuaPengguna = User::query()->pluck('id')->all();
        if (empty($semuaPengguna)) {
            return;
        }

        $semuaResep = Resep::query()->pluck('id')->all();
        if (empty($semuaResep)) {
            return;
        }

        // Bersihkan dulu biar idempoten
        Suka::query()->delete();
        Simpanan::query()->delete();

        // Distribusikan suka profil untuk semua pengguna (kebanyakan suka beberapa profil)
        $penggunaAktif = array_slice($semuaPengguna, 0, min(150, count($semuaPengguna)));
        foreach ($penggunaAktif as $pemberiId) {
            // Suka 5-25 profil secara acak
            $jumlahSukaProfil = rand(5, 25);
            $targetProfil = array_values(array_diff($semuaPengguna, [$pemberiId]));
            shuffle($targetProfil);
            $targetProfil = array_slice($targetProfil, 0, $jumlahSukaProfil);
            foreach ($targetProfil as $profilId) {
                Suka::query()->firstOrCreate([
                    'user_id' => $pemberiId,
                    'sukaable_type' => Suka::PROFIL,
                    'sukaable_id' => $profilId,
                ]);
            }
        }

        // Distribusikan suka resep - buat terlihat populer
        foreach ($semuaResep as $resepId) {
            // Setiap resep disukai 15-80 pengguna
            $jumlahSukaResep = rand(15, 80);
            shuffle($penggunaAktif);
            $pemberi = array_slice($penggunaAktif, 0, $jumlahSukaResep);
            foreach ($pemberi as $pemberiId) {
                Suka::query()->firstOrCreate([
                    'user_id' => $pemberiId,
                    'sukaable_type' => Suka::RESEP,
                    'sukaable_id' => $resepId,
                ]);
            }
        }

        // Distribusikan simpanan - membuat terlihat banyak yang disimpan
        foreach ($semuaResep as $resepId) {
            // Setiap resep disimpan 10-60 pengguna
            $jumlahSimpan = rand(10, 60);
            shuffle($penggunaAktif);
            $penyimpan = array_slice($penggunaAktif, 0, $jumlahSimpan);
            foreach ($penyimpan as $penyimpanId) {
                Simpanan::query()->firstOrCreate([
                    'user_id' => $penyimpanId,
                    'resep_id' => $resepId,
                ]);
            }
        }

        $this->command?->info('Engagement populasi selesai: suka profil='.Suka::query()->where('sukaable_type', Suka::PROFIL)->count().', suka resep='.Suka::query()->where('sukaable_type', Suka::RESEP)->count().', simpanan='.Simpanan::query()->count());
    }
}
