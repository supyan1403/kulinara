<?php

/**
 * Perbaikan satu kali: dua resep nasi goreng memakai kalimat pembuka Wikipedia
 * yang identik persis — 355 karakter yang sama, tanpa satu pun pembeda, sehingga
 * keduanya terbaca sebagai salinan.
 *
 *   php scripts/perbaiki-deskripsi-wikipedia.php             pemeriksaan + ringkasan, tidak menulis
 *   php scripts/perbaiki-deskripsi-wikipedia.php --terapkan  tulis ke database
 *
 * Berbeda dari perbaiki-deskripsi.php, kalimat penggantinya ditulis tangan di
 * sini, bukan dihasilkan `ResepSeeder::deskripsiCadangan()`. Kedua resep ini
 * punya kolom `bahan` kosong, jadi mesin tidak punya pegangan apa pun untuk
 * membedakannya — pembedanya harus datang dari pengetahuan soal hidangannya,
 * bukan dari kata acak.
 *
 * `updated_at` dibiarkan — ini penyuntingan data benih, bukan aktivitas penulis.
 *
 * Empat pemeriksaan yang sama dengan perbaiki-deskripsi.php tetap dijalankan:
 *   1. kata "rasa" tidak boleh berdiri dua kali dalam satu kalimat;
 *   2. tidak boleh ada kata sifat yang berulang di dalam satu kalimat;
 *   3. jajanan tidak boleh berpasangan dengan lauk, dan sebaliknya;
 *   4. tidak boleh ada dua kalimat kembar meski judulnya berbeda.
 */

use App\Models\Resep;
use Database\Seeders\ResepSeeder;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

/**
 * Ekal kalimat Wikipedia yang dimaksud. Sengaja dipakai sebagai pencari:
 * kalau nanti ada resep ketiga yang kebagian kalimat yang sama, ia otomatis
 * ikut terdeteksi dan masuk laporan.
 */
const EKAL_WIKIPEDIA = 'Nasi goreng merupakan sajian nasi yang digoreng dalam sebuah wajan';

/** Kata yang tidak boleh muncul pada deskripsi jajanan. */
const TERLARANG_JAJANAN = ['nasi', 'sambal', 'lalapan', 'kerupuk', 'bawang', 'cabai', 'tahu goreng'];

/** Kata yang tidak boleh muncul pada deskripsi lauk. */
const TERLARANG_LAUK = ['kopi', 'teh manis', 'gula aren', 'santan hangat'];

/**
 * Kalimat penggantian, ditulis tangan.
 *
 * Nasi goreng seafood tidak punya daerah dan tidak punya daftar bahan, jadi
 * kalimatnya bertumpu pada dua hal yang benar-benar membedakan hidangan ini
 * dari nasi goreng lain: aroma laut dari seafood yang baru masuk wok panas,
 * dan sisi wajan yang karamel sampai sedikit gosong.
 *
 * Nasi Goreng Kampung dibedakan oleh caranya dibuat, bukan oleh isinya —
 * ulekan kasar, terasi bakar, dan telur mata sapi yang kuningnya masih encer.
 */
const PENGGANTI = [
    // Resep paling polos dari ketiganya: pembedanya bukan isinya, melainkan
    // bahan dasarnya sendiri. Nasi dingin adalah detail yang benar-benar
    // membedakan nasi goreng yang pera dari yang lembek — dan detail itu
    // tidak pernah disebut kalimat Wikipedia yang lama.
    'nasi-goreng' => 'Nasi putih dingin yang digoreng bersama bawang merah sampai kecokelatan dan kecap manis yang membalut tiap butirnya — wanginya menempel lama di dapur.',
    'nasi-goreng-seafood' => 'Udang dan cumi yang baru dilepas di wok panas menyisakan aroma laut pekat pada tiap butir nasi, dipadu karamelisasi kecap manis yang lengket dan sedikit gosong di sisi wajan.',
    'nasi-goreng-kampung' => 'Bawang merah diulek kasar hingga berminyak dan wangi, terasi bakar yang samar di belakangnya, lalu telur mata sapi dengan kuningnya yang masih encer.',
];

$terapkan = in_array('--terapkan', $argv, true);

$target = Resep::query()
    ->where(function ($q) {
        $q->where('deskripsi', 'like', '%'.EKAL_WIKIPEDIA.'%')
            ->orWhereIn('slug', array_keys(PENGGANTI));
    })
    ->get(['id', 'judul', 'slug', 'kategori', 'daerah', 'deskripsi']);

printf("resep terdeteksi: %d\n\n", $target->count());

$baru = $target->map(fn (Resep $r) => [
    'resep' => $r,
    'deskripsi' => PENGGANTI[$r->slug] ?? null,
]);

$masalah = collect();

$baru->each(function (array $b) use (&$masalah) {
    $judul = $b['resep']->judul;

    if ($b['deskripsi'] === null) {
        $masalah->push("{$judul}: tidak ada kalimat penggantian yang disiapkan untuk slug {$b['resep']->slug}");

        return;
    }

    $kunci = ResepSeeder::kunciDeskripsi($judul, $b['deskripsi']);
    $b['kunci'] = $kunci;

    if (preg_match_all('/\brasa\b/i', $kunci) > 1) {
        $masalah->push("{$judul}: kata 'rasa' berdiri dua kali");
    }

    $berulang = ResepSeeder::kataBerulang($judul, $b['deskripsi']);

    if ($berulang !== []) {
        $masalah->push("{$judul}: kata berulang — ".implode(', ', $berulang));
    }

    $jajan = $b['resep']->kategori->value === 'jajanan';

    foreach ($jajan ? TERLARANG_JAJANAN : TERLARANG_LAUK as $kata) {
        if (stripos($kunci, $kata) !== false) {
            $masalah->push(sprintf('%s (%s): mengandung "%s"', $judul, $jajan ? 'jajanan' : 'lauk', $kata));
        }
    }

    if ($b['deskripsi'] === $b['resep']->deskripsi) {
        $masalah->push("{$judul}: kalimat baru sama persis dengan yang lama");
    }
});

$baru->filter(fn (array $b) => isset($b['kunci']))
    ->groupBy(fn (array $b) => $b['kunci'])
    ->filter(fn ($g) => $g->count() > 1)
    ->each(fn ($g) => $masalah->push('kalimat kembar: '.$g->map(fn (array $b) => $b['resep']->judul)->implode(', ')));

echo "Penggantian:\n";

foreach ($baru as $b) {
    printf("\n  %s\n", $b['resep']->judul);
    printf("    - %s\n", $b['resep']->deskripsi);
    printf("    + %s\n", $b['deskripsi'] ?? '(TIDAK ADA)');
}

printf("\npemeriksaan: %s\n", $masalah->isEmpty() ? 'lolos semua' : 'GAGAL');

foreach ($masalah as $m) {
    printf("  ! %s\n", $m);
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
$cadangan = storage_path('framework/deskripsi-wikipedia-lama.json');
file_put_contents($cadangan, json_encode(
    $baru->mapWithKeys(fn (array $b) => [$b['resep']->slug => $b['resep']->deskripsi])->all(),
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
));

printf("\nCadangan %d deskripsi lama: %s\n", $baru->count(), $cadangan);

foreach ($baru as $b) {
    Resep::query()->whereKey($b['resep']->id)->update(['deskripsi' => $b['deskripsi']]);
}

printf("Tersimpan: %d deskripsi.\n", $baru->count());
