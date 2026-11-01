<?php

namespace App\Observers;

use App\Models\Resep;
use App\Models\Ulasan;

/**
 * Menjaga kolom `rata_rating` dan `jumlah_ulasan` di tabel `resep` tetap
 * sinkron dengan isi tabel `ulasan`.
 *
 * Inilah satu-satunya tempat kedua kolom itu ditulis. Dengan begitu tidak ada
 * jalur — controller, seeder, command, maupun tinker — yang bisa mengubah
 * ulasan tanpa memperbarui ringkasannya, karena semua penulisan lewat model
 * memicu observer ini.
 *
 * Ketiga kejadian perlu ditangani, bukan hanya `created`:
 *  - `created` : ulasan baru menambah hitungan
 *  - `updated` : pengguna mengubah bintangnya, rata-rata harus bergeser
 *  - `deleted` : ulasan dihapus (oleh pengguna atau Pengelola) mengurangi
 *
 * `saved` sengaja tidak dipakai. Ia menyala pada create dan update sekaligus,
 * jadi akan menghitung ulang dua kali untuk satu penyimpanan.
 */
class UlasanObserver
{
    public function created(Ulasan $ulasan): void
    {
        $this->sinkronkan($ulasan->resep_id);
    }

    public function updated(Ulasan $ulasan): void
    {
        // `resep_id` pada prinsipnya tidak pernah berpindah, tapi kalau suatu
        // saat bisa, resep lama juga harus dihitung ulang — kalau tidak,
        // ulasan yang pindah akan tetap terhitung di resep asalnya.
        if ($ulasan->wasChanged('resep_id')) {
            $this->sinkronkan($ulasan->getOriginal('resep_id'));
        }

        $this->sinkronkan($ulasan->resep_id);
    }

    public function deleted(Ulasan $ulasan): void
    {
        $this->sinkronkan($ulasan->resep_id);
    }

    /**
     * Hitung ulang ringkasan resep yang terpengaruh.
     *
     * Seluruh logikanya ada di `Resep::sinkronkanRating()`; kelas ini hanya
     * menerjemahkan kejadian pada ulasan menjadi panggilan ke sana.
     */
    private function sinkronkan(?int $resepId): void
    {
        if (! $resepId) {
            return;
        }

        Resep::query()->whereKey($resepId)->first()?->sinkronkanRating();
    }
}
