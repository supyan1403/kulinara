<?php

namespace App\Console\Commands;

use App\Models\Resep;
use App\Models\Ulasan;
use Illuminate\Console\Command;

/**
 * Hitung ulang kolom ringkasan rating untuk seluruh resep.
 *
 * Kolom `rata_rating` dan `jumlah_ulasan` diperbarui otomatis oleh observer
 * `Ulasan`, jadi command ini bukan bagian dari alur normal. Ia ada untuk dua
 * keadaan:
 *
 *  1. Setelah pemulihan basis data, impor, atau pengubahan data langsung lewat
 *     SQL — jalur-jalur yang melewati Eloquent sehingga observer tidak jalan.
 *  2. Sebagai pemeriksa: `--kering` menampilkan resep yang angkanya berbeda
 *     dari hasil hitung sebenarnya, tanpa mengubah apa pun.
 *
 * Sengaja memakai `updateQuietly` per resep, bukan satu UPDATE massal: kolom
 * ringkasan bukan sekadar angka, ia dipakai untuk memutuskan tampilan, jadi
 * lebih baik jelas dan bisa ditelusuri daripada pintar.
 */
class RatingHitungUlang extends Command
{
    protected $signature = 'rating:hitung-ulang
                            {--kering : Tampilkan saja yang tidak cocok, jangan ubah apa pun}';

    protected $description = 'Hitung ulang rata_rating dan jumlah_ulasan dari isi tabel ulasan';

    public function handle(): int
    {
        $kering = (bool) $this->option('kering');
        $berubah = 0;
        $diperiksa = 0;

        Resep::query()
            ->select(['id', 'judul', 'rata_rating', 'jumlah_ulasan'])
            ->chunkById(200, function ($resepList) use (&$berubah, &$diperiksa, $kering) {
                // Satu query agregat untuk seluruh batch, bukan satu per resep.
                // Penting saat data sudah banyak: memanggil AVG di dalam
                // perulangan akan menghabiskan waktu lebih lama daripada
                // memperbaiki masalahnya.
                $agregat = Ulasan::query()
                    ->whereIn('resep_id', $resepList->pluck('id'))
                    ->groupBy('resep_id')
                    ->selectRaw('resep_id, COUNT(*) as j, AVG(rating) as rata')
                    ->get()
                    ->keyBy('resep_id');

                foreach ($resepList as $resep) {
                    $diperiksa++;

                    $baris = $agregat->get($resep->id);
                    $j = (int) ($baris->j ?? 0);
                    $rata = $j === 0 ? 0 : round((float) $baris->rata, 2);

                    $sama = $j === (int) $resep->jumlah_ulasan
                        && abs($rata - (float) $resep->rata_rating) < 0.005;

                    if ($sama) {
                        continue;
                    }

                    $berubah++;

                    if ($kering) {
                        $this->line(sprintf(
                            '  #%d %s — tersimpan %s/%d, seharusnya %s/%d',
                            $resep->id,
                            $resep->judul,
                            $resep->rata_rating,
                            $resep->jumlah_ulasan,
                            $rata,
                            $j,
                        ));

                        continue;
                    }

                    Resep::query()->whereKey($resep->id)->update([
                        'rata_rating' => $rata,
                        'jumlah_ulasan' => $j,
                    ]);
                }
            });

        if ($kering) {
            $this->info($berubah === 0
                ? "Semua cocok ({$diperiksa} resep diperiksa)."
                : "{$berubah} dari {$diperiksa} resep tidak cocok. Jalankan tanpa --kering untuk memperbaiki.");

            return self::SUCCESS;
        }

        $this->info($berubah === 0
            ? "Tidak ada yang perlu diperbaiki ({$diperiksa} resep diperiksa)."
            : "{$berubah} dari {$diperiksa} resep diperbarui.");

        return self::SUCCESS;
    }
}
