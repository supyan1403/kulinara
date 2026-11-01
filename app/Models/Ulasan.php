<?php

namespace App\Models;

use Database\Factories\UlasanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris = satu penilaian seorang pengguna atas satu resep.
 *
 * Satu pengguna hanya boleh punya satu ulasan per resep. Batas itu ditegakkan
 * `unique(['resep_id', 'user_id'])` di basis data, jadi kelas ini tidak
 * mengulanginya dengan pemeriksaan `exists()` yang bisa kalah balapan.
 *
 * Memperbarui nilai dilakukan lewat `update()`, bukan membuat baris baru —
 * itulah sebabnya `rating` dan `komentar` ada di `#[Fillable]`.
 *
 * PENTING — JANGAN memakai penghapusan massal:
 *
 *     Ulasan::query()->where(...)->delete()      // SALAH
 *
 * Penghapusan massal berjalan langsung di basis data dan TIDAK memicu event
 * `deleted`, sehingga `UlasanObserver` tidak jalan dan `rata_rating` /
 * `jumlah_ulasan` di tabel resep berhenti cocok dengan isinya. Tidak ada galat
 * yang muncul — angkanya hanya jadi salah, dan tetap salah.
 *
 * Hapus satu per satu lewat model:
 *
 *     foreach ($daftar as $ulasan) { $ulasan->delete(); }
 *
 * Kalau memang terpaksa memakai penghapusan massal (misalnya membersihkan data
 * dalam jumlah besar), jalankan `php artisan rating:hitung-ulang` sesudahnya.
 */
#[Fillable(['user_id', 'resep_id', 'rating', 'komentar', 'balasan', 'balasan_at'])]
class Ulasan extends Model
{
    /** @use HasFactory<UlasanFactory> */
    use HasFactory;

    /**
     * Nama tabel eksplisit — "ulasan" tidak berubah bentuk dalam bahasa
     * Indonesia, sehingga pluralisasi bawaan Laravel ("ulasans") salah.
     */
    protected $table = 'ulasan';

    /**
     * Batas rating yang diterima, dari terendah ke tertinggi.
     *
     * Dipakai bersama oleh validasi formulir dan penggambaran bintang di
     * antarmuka. Ditaruh di konstanta supaya keduanya tidak bisa menyimpang —
     * menambah bintang di UI tanpa mengubah validasi akan menghasilkan nilai
     * yang ditolak server setelah pengguna menekannya.
     */
    public const RATING_MIN = 1;

    public const RATING_MAKS = 5;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'balasan_at' => 'datetime',
        ];
    }

    /**
     * Penulis ulasan.
     */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Resep yang diulas.
     */
    public function resep(): BelongsTo
    {
        return $this->belongsTo(Resep::class, 'resep_id');
    }

    /**
     * Apakah ulasan ini sudah dibalas pemilik resep.
     */
    public function sudahDibalas(): bool
    {
        return $this->balasan !== null && $this->balasan !== '';
    }
}
