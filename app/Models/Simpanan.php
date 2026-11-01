<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris = satu penanda "resep ini disimpan" oleh satu pengguna.
 *
 * Barisnya sendiri tidak menyimpan apa pun selain pasangan ID; seluruh isi
 * resep tetap dibaca lewat relasi `resep()`, sehingga tidak ada data ganda
 * yang bisa menyimpang.
 */
#[Fillable(['user_id', 'resep_id'])]
class Simpanan extends Model
{
    /**
     * Nama tabel eksplisit — sama seperti pada `resep`: jamak "simpanan"
     * tidak diubah otomatis oleh Laravel menjadi "simpanans".
     */
    protected $table = 'simpanan';

    /**
     * Pengguna yang menyimpan resep ini.
     */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Resep yang disimpan.
     */
    public function resep(): BelongsTo
    {
        return $this->belongsTo(Resep::class, 'resep_id');
    }
}
