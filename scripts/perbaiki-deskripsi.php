<?php

/**
 * Perbaikan satu kali: ganti deskripsi templat untuk hidangan yang tidak punya
 * artikel Wikipedia.
 *
 *   php scripts/perbaiki-deskripsi.php             pemeriksaan + ringkasan, tidak menulis
 *   php scripts/perbaiki-deskripsi.php --terapkan  tulis ke database
 *
 * Pencocokannya lewat ekor kalimat lama yang tetap
 * (", mudah dibuat di rumah, dan cocok disajikan bersama"), sehingga 287
 * deskripsi hasil ringkasan Wikipedia tidak mungkin tersentuh. `updated_at`
 * sengaja dibiarkan — ini penyuntingan data benih, bukan aktivitas penulis.
 *
 * Empat pemeriksaan dijalankan sebelum apa pun ditulis:
 *   1. kata "rasa" tidak boleh berdiri dua kali dalam satu kalimat;
 *   2. tidak boleh ada kata sifat yang berulang di dalam satu kalimat;
 *   3. jajanan tidak boleh berpasangan dengan lauk, dan sebaliknya;
 *   4. tidak boleh ada dua kalimat kembar meski judulnya berbeda.
 */

use App\Enums\KategoriResep;
use App\Models\Resep;
use Database\Seeders\ResepSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

const POLA_LAMA = '%mudah dibuat di rumah, dan cocok disajikan bersama%';

/** Kata yang tidak boleh muncul pada deskripsi jajanan. */
const TERLARANG_JAJANAN = ['nasi', 'sambal', 'lalapan', 'kerupuk', 'bawang', 'cabai', 'tahu goreng'];

/** Kata yang tidak boleh muncul pada deskripsi lauk. */
const TERLARANG_LAUK = ['kopi', 'teh manis', 'gula aren', 'santan hangat'];

$terapkan = in_array('--terapkan', $argv, true);

$target = Resep::query()->where('deskripsi', 'like', POLA_LAMA)->get();

printf("resep total                 : %d\n", Resep::query()->count());
printf("kena pola deskripsi templat : %d\n", $target->count());

if ($target->isEmpty()) {
    echo "Tidak ada yang perlu diperbaiki.\n";

    return;
}

// Bentuk seluruh kalimat baru tanpa menyentuh database, supaya pemeriksaan
// di bawah bisa menolak proses bila ada satu pun yang gagal. Sama seperti di
// seeder: kunci kalimat dikumpulkan berurutan sehingga tabrakan ruang
// kombinasi saling terhindari.
$baru = collect();
$dipakai = [];

foreach ($target as $r) {
    $deskripsi = ResepSeeder::deskripsiCadangan(
        $r->judul,
        $r->slug,
        $r->kategori->value,
        $r->daerah,
        $r->bahan,
        $dipakai,
    );

    $kunci = ResepSeeder::kunciDeskripsi($r->judul, $deskripsi);
    $dipakai[] = $kunci;

    $baru->push(['resep' => $r, 'deskripsi' => $deskripsi, 'kunci' => $kunci]);
}

$masalah = collect();

foreach ($baru as $b) {
    $jajan = $b['resep']->kategori->value === KategoriResep::Jajanan->value;

    if (preg_match_all('/\brasa\b/i', $b['kunci']) > 1) {
        $masalah->push("{$b['resep']->judul}: kata 'rasa' berdiri dua kali");
    }

    $berulang = ResepSeeder::kataBerulang($b['resep']->judul, $b['deskripsi']);

    if ($berulang !== []) {
        $masalah->push("{$b['resep']->judul}: kata berulang — ".implode(', ', $berulang));
    }

    foreach ($jajan ? TERLARANG_JAJANAN : TERLARANG_LAUK as $kata) {
        if (stripos($b['kunci'], $kata) !== false) {
            $masalah->push(sprintf(
                '%s (%s): mengandung "%s"',
                $b['resep']->judul,
                $jajan ? 'jajanan' : 'lauk',
                $kata,
            ));
        }
    }
}

$baru->groupBy(fn (array $b) => $b['kunci'])
    ->filter(fn ($g) => $g->count() > 1)
    ->each(fn ($g) => $masalah->push('kalimat kembar: '.$g->map(fn (array $b) => $b['resep']->judul)->implode(', ')));

printf("varian kalimat baru         : %d unik dari %d\n", $baru->unique('kunci')->count(), $baru->count());
printf("pemeriksaan                 : %s\n", $masalah->isEmpty() ? 'lolos semua' : 'GAGAL');

foreach ($masalah as $m) {
    printf("  ! %s\n", $m);
}

echo "\nContoh penggantian:\n";

foreach ($baru->take(5) as $c) {
    printf("\n  %s\n", $c['resep']->judul);
    printf("    - %s\n", $c['resep']->deskripsi);
    printf("    + %s\n", $c['deskripsi']);
}

if (! $terapkan) {
    echo "\nIni baru ringkasan. Ulangi dengan --terapkan untuk menyimpannya.\n";

    return;
}

if ($masalah->isNotEmpty()) {
    echo "\nDibatalkan: perintah ini menolak menulis selama masih ada kalimat yang gagal.\n";

    exit(1);
}

// Cadangan sebelum menulis. Murah, dan membuat keputusan ini mudah dibatalkan.
$cadangan = storage_path('framework/deskripsi-lama.json');
file_put_contents($cadangan, json_encode(
    $baru->mapWithKeys(fn (array $b) => [$b['resep']->slug => $b['resep']->deskripsi])->all(),
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
));

printf("\nCadangan 98 deskripsi lama: %s\n", $cadangan);

foreach ($baru as $b) {
    DB::table('resep')->where('id', $b['resep']->id)->update(['deskripsi' => $b['deskripsi']]);
}

printf("\n%d deskripsi diperbarui.\n", $baru->count());
printf("sisa pola lama: %d (harus 0)\n", Resep::query()->where('deskripsi', 'like', POLA_LAMA)->count());
