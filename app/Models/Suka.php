<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris = satu "suka" dari satu pengguna atas satu target.
 *
 * Dua jenis target lewat kolom polymorphic `sukaable_*`:
 *
 *   - 'Profil' — suka profil seseorang (anggukan ke si koki)
 *   - 'Resep'  — suka sebuah masakan
 *
 * Kunci unik (user_id, sukaable_type, sukaable_id) di basis data yang
 * menjamin satu pengguna hanya menyukai satu kali per target, sehingga
 * model ini tidak perlu pemeriksaan `exists()` yang bisa kalah balapan.
 *
 * Sama seperti Simpanan, seluruh isi target dibaca lewat relasi masing-masing
 * — baris ini hanya menyimpan pasangan ID.
 */
#[Fillable(['user_id', 'sukaable_type', 'sukaable_id'])]
class Suka extends Model
{
    /** Nilai `sukaable_type` untuk suka profil pengguna. */
    public const PROFIL = 'Profil';

    /** Nilai `sukaable_type` untuk suka resep. */
    public const RESEP = 'Resep';

    /**
     * Nama tabel eksplisit: "suka" jamak tetap "suka" dalam bahasa Indonesia,
     * sehingga pluralisasi bawaan Laravel ("sukas") tidak berlaku.
     */
    protected $table = 'suka';

    /**
     * Target berupa resep.
     *
     * Sengaja dibuat sebagai `belongsTo` biasa — BUKAN `morphTo`. Kolom
     * `sukaable_type` menyimpan string literal 'Profil'/'Resep', bukan
     * nama class, sehingga `morphTo` akan mencoba `new App\Models\Profil`
     * waktu query `whereHas('target')` dan menjatuhkan "Class not found".
     *
     * Filter tipe TIDAK ditaruh di sini: `->where()` pada relasi belongsTo
     * ikut menempel ke query tabel `resep` (bukan tabel `suka`) sehingga
     * menjatuhkan "column sukaable_type does not exist". Pemfilteran tipe
     * sepenuhnya tanggung jawab query induk — lihat `User::sukaan()`.
     */
    public function resep(): BelongsTo
    {
        return $this->belongsTo(Resep::class, 'sukaable_id');
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
