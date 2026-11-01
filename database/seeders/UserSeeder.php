<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Isi database dengan pengguna contoh dalam jumlah besar.
 *
 * Nama diambil dari Faker berbahasa Indonesia (locale id_ID), bukan pool nama
 * buatan tangan, supaya daftar chef terasa seperti orang sungguhan. Kata
 * gelar seperti "S.Kom" atau "M.Ak" disaring dulu sebelum dipakai membuat
 * alamat surel, agar tidak menghasilkan slug aneh.
 */
class UserSeeder extends Seeder
{
    /** Jumlah pengguna contoh — penulis resep. */
    private const JUMLAH_PENULIS = 120;

    /** Jumlah pengguna resep contoh (bukan penulis kurasi). */
    private const JUMLAH_PENGGUNA = 40;

    /**
     * Empat penulis tetap milik resep kurasi — namanya dipakai di kartu hero,
     * jadi harus stabil antar run dan tidak boleh ditimpa.
     *
     * @var array<int, array{name: string, email: string}>
     */
    private const PENULIS_TETAP = [
        ['name' => 'Rena Sumardi', 'email' => 'rena@kulinara.test'],
        ['name' => 'Bagus Prakoso', 'email' => 'bagus@kulinara.test'],
        ['name' => 'Ayu Lestari', 'email' => 'ayu@kulinara.test'],
        ['name' => 'Tio Wijaya', 'email' => 'tio@kulinara.test'],
    ];

    /** @var array<int, string> */
    private array $surelDipakai = [];

    /**
     * Buat akun-akun contoh. Deterministik lewat firstOrCreate by email,
     * sehingga seeding bisa dijalankan ulang tanpa menumpuk baris.
     */
    public function run(): void
    {
        foreach (self::PENULIS_TETAP as $penulis) {
            $this->buat($penulis['name'], $penulis['email'], UserRole::Pengguna, 900);
        }

        foreach (range(1, self::JUMLAH_PENULIS) as $i) {
            $this->buat(fake('id_ID')->name(), null, UserRole::Pengguna, 720);
        }

        foreach (range(1, self::JUMLAH_PENGGUNA) as $i) {
            $this->buat(fake('id_ID')->name(), null, UserRole::Pengguna, 540);
        }
    }

    /**
     * Buat satu pengguna. `createdAt` diacak mundur supaya urutan "anggota
     * terbaru" di aplikasi tidak terlihat seperti hasil ekspor massal.
     */
    private function buat(string $nama, ?string $surel, UserRole $peran, int $rentangHari): User
    {
        $surel ??= $this->surelDari($nama);

        return User::query()->firstOrCreate(
            ['email' => $surel],
            [
                'name' => $nama,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => $peran,
                'created_at' => now()->subDays(fake()->numberBetween(1, $rentangHari)),
                'updated_at' => now(),
            ],
        );
    }

    /**
     * Susun alamat surel dari nama, buang kata gelar, dan tambahkan angka
     * hanya bila sudah dipakai akun lain.
     */
    private function surelDari(string $nama): string
    {
        $kata = array_values(array_filter(
            preg_split('/\s+/', $nama) ?: [],
            fn (string $k) => ! preg_match('/^[A-Z]\.[A-Za-z]+$/', $k),
        ));

        $dasar = Str::slug(implode('-', array_slice($kata, 0, 2))) ?: 'pengguna';
        $surel = "{$dasar}@kulinara.test";
        $suffiks = 1;

        while (in_array($surel, $this->surelDipakai, true) || User::query()->where('email', $surel)->exists()) {
            $surel = $dasar.$suffiks++.'@kulinara.test';
        }

        $this->surelDipakai[] = $surel;

        return $surel;
    }
}
