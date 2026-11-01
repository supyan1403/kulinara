<?php

namespace App\Models;

use App\Enums\BahanKategori;
use App\Enums\SatuanDasar;
use Database\Factories\BahanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Rujukan bahan pangan — satu baris per bahan dasar, lengkap dengan nilai
 * gizi per 100 gram/mililiter.
 *
 * Tabel ini adalah fondasi kalkulasi gizi otomatis: `KalkulasiGizi` membaca
 * `faktor_densitas` (ml ↔ gram) dan `berat_per_unit` (siung, butir, buah)
 * untuk mengubah takaran dapur menjadi berat yang benar-benar bisa dikalikan
 * dengan nilai gizi.
 *
 * Resep TIDAK menyimpan salinan gizi bahan — ia hanya menyimpan
 * `bahan_id` di dalam kolom jsonb `bahan`, sehingga angka gizi di sini
 * bisa diperbaiki sekali dan langsung berlaku untuk semua resep.
 */
class Bahan extends Model
{
    /** @use HasFactory<BahanFactory> */
    use HasFactory;

    /**
     * Tabel rujukan ini sengaja pakai nama `bahan_pangan` (bukan `bahan`)
     * supaya tidak bentrok dengan kolom jsonb `bahan` di tabel resep.
     */
    protected $table = 'bahan_pangan';

    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kategori' => BahanKategori::class,
            'satuan_dasar' => SatuanDasar::class,
            'energi_kkal' => 'decimal:2',
            'protein_g' => 'decimal:2',
            'lemak_g' => 'decimal:2',
            'karbohidrat_g' => 'decimal:2',
            'faktor_densitas' => 'decimal:3',
            'berat_per_unit' => 'decimal:2',
            'aktif' => 'boolean',
        ];
    }

    /**
     * Cari bahan berdasarkan sebagian nama (autocomplete di Studio Dapur).
     *
     * Pemakaian `ILIKE '%kata%'` memang tidak memakai index b-tree biasa,
     * tapi tabel ini berukuran kecil (ratusan baris) sehingga penukarannya
     * sepadan dengan keluwesan pencarian tanpa peduli huruf besar/kecil.
     */
    public function scopeCari(Builder $query, string $kata): Builder
    {
        $kata = trim($kata);

        if ($kata === '') {
            return $query;
        }

        return $query->where('nama', 'like', "%{$kata}%");
    }

    /**
     * Hanya bahan yang masih dipakai (tidak dicabut dari rujukan).
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    /**
     * Nilai gizi bahan ini per 1 gram.
     *
     * Bahan berbasis berat: nilai gizi tercatat per 100 gram, jadi tinggal
     * dibagi 100. Bahan cair: nilai gizi tercatat per 100 mililiter, jadi
     * turunannya harus ikut dibagi `faktor_densitas` (gram per mililiter) —
     * 100 ml santan kental bukan 100 gram.
     *
     * @return array{energi_kkal: float, protein_g: float, lemak_g: float, karbohidrat_g: float}
     */
    public function giziPerGram(): array
    {
        $pembagi = $this->satuan_dasar === SatuanDasar::Mililiter
            ? 100 * (float) $this->faktor_densitas
            : 100;

        return [
            'energi_kkal' => ((float) $this->energi_kkal) / $pembagi,
            'protein_g' => ((float) $this->protein_g) / $pembagi,
            'lemak_g' => ((float) $this->lemak_g) / $pembagi,
            'karbohidrat_g' => ((float) $this->karbohidrat_g) / $pembagi,
        ];
    }
}
