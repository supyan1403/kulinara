<?php

namespace Database\Seeders;

use App\Enums\BahanKategori;
use App\Enums\SatuanDasar;
use App\Models\Bahan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Rujukan bahan pangan dasar Indonesia.
 *
 * Sumber angkanya adalah perkiraan dari Tabel Komposisi Pangan Indonesia
 * (TKPI) yang dibulatkan — bukan angka laboratorium. Nilai ini memang
 * dirancang untuk diperbaiki: seluruh baris ada di
 * `database/seeders/data/bahan_pangan.csv`, sehingga memperbarui angka
 * gizi cukup dengan menyunting CSV dan menjalankan ulang seeder ini.
 *
 * Seeder ini idempoten — memakai `updateOrCreate` berdasarkan `kode`,
 * jadi aman dijalankan berkali-kali tanpa menggandakan data, dan
 * perbaikan angka di CSV langsung menimpa nilai lama.
 */
class BahanSeeder extends Seeder
{
    /**
     * Jalankan migrasi database.
     */
    public function run(): void
    {
        $berkas = database_path('seeders/data/bahan_pangan.csv');

        if (! File::exists($berkas)) {
            $this->command?->warn('Berkas bahan_pangan.csv tidak ditemukan — seeder dilewati.');

            return;
        }

        $baris = $this->bacaCsv($berkas);

        foreach ($baris as $isi) {
            Bahan::query()->updateOrCreate(
                ['kode' => $isi['kode']],
                [
                    'nama' => $isi['nama'],
                    'kategori' => BahanKategori::from($isi['kategori']),
                    'satuan_dasar' => SatuanDasar::from($isi['satuan_dasar']),
                    'energi_kkal' => $isi['energi_kkal'],
                    'protein_g' => $isi['protein_g'],
                    'lemak_g' => $isi['lemak_g'],
                    'karbohidrat_g' => $isi['karbohidrat_g'],
                    'faktor_densitas' => $isi['faktor_densitas'] ?: 1,
                    'berat_per_unit' => $isi['berat_per_unit'] ?: null,
                    'satuan_takaran' => $isi['satuan_takaran'] ?: null,
                    'aktif' => true,
                ],
            );
        }

        $this->command?->info(count($baris).' bahan pangan tersimpan.');
    }

    /**
     * Baca CSV jadi larik asosiatif, lewati baris komentar dan baris kosong.
     *
     * @return array<int, array<string, string>>
     */
    private function bacaCsv(string $berkas): array
    {
        $isi = File::get($berkas);
        $baris = array_map('trim', explode("\n", str_replace("\r\n", "\n", $isi)));

        // Buang baris judul dan baris yang dikomentari (diawali #).
        $baris = array_values(array_filter(
            $baris,
            fn (string $b) => $b !== '' && ! str_starts_with($b, '#'),
        ));

        $kepala = array_map('trim', explode(',', array_shift($baris)));

        return array_map(function (string $b) use ($kepala) {
            $kolom = array_map('trim', explode(',', $b));

            return array_combine($kepala, $kolom);
        }, $baris);
    }
}
