<?php

namespace Database\Factories;

use App\Enums\BahanKategori;
use App\Enums\SatuanDasar;
use App\Models\Bahan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bahan>
 */
class BahanFactory extends Factory
{
    protected $model = Bahan::class;

    /**
     * Definisi bahan contoh — beras putih, dengan angka yang mendekati
     * Tabel Komposisi Pangan Indonesia.
     */
    public function definition(): array
    {
        return [
            'kode' => 'BHN-'.fake()->unique()->numerify('###'),
            'nama' => 'Beras putih',
            'kategori' => BahanKategori::Karbohidrat,
            'satuan_dasar' => SatuanDasar::Gram,
            'energi_kkal' => 360,
            'protein_g' => 6.7,
            'lemak_g' => 0.7,
            'karbohidrat_g' => 78.8,
            'faktor_densitas' => 1,
            'berat_per_unit' => null,
            'satuan_takaran' => null,
            'aktif' => true,
        ];
    }

    /**
     * Bahan cair — memakai densitas nyata, dipakai untuk menguji konversi
     * ml ↔ gram di `KalkulasiGiziTest`.
     */
    public function cair(): static
    {
        return $this->state(fn () => [
            'nama' => 'Santan kental',
            'kategori' => BahanKategori::Cairan,
            'satuan_dasar' => SatuanDasar::Mililiter,
            'energi_kkal' => 190,
            'protein_g' => 2,
            'lemak_g' => 19,
            'karbohidrat_g' => 5,
            'faktor_densitas' => 0.9,
            'satuan_takaran' => 'ml',
        ]);
    }

    /**
     * Bahan dengan takaran "butir" — membutuhkan `berat_per_unit`,
     * dipakai untuk menguji konversi siung/butir → gram.
     */
    public function butiran(): static
    {
        return $this->state(fn () => [
            'nama' => 'Bawang merah',
            'kategori' => BahanKategori::BumbuRempah,
            'satuan_dasar' => SatuanDasar::Gram,
            'energi_kkal' => 72,
            'protein_g' => 1.5,
            'lemak_g' => 0.1,
            'karbohidrat_g' => 16.5,
            'faktor_densitas' => 1,
            'berat_per_unit' => 7,
            'satuan_takaran' => 'siung',
        ]);
    }
}
