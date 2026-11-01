<?php

/**
 * Pemeriksaan read-only: seberapa palsu kolom `bahan` di 385 resep benih.
 *
 *   php scripts/tinjau-bahan.php
 *
 * Tidak menulis apa pun ke database. Tiga pertanyaan dijawab dengan angka:
 *
 *   1. Bukti template — tiap kategori punya berapa varian SET NAMA bahan?
 *      Kalau satu kategori berisi puluhan resep hanya dengan satu varian,
 *      itu bukti keras bahwa bahannya dihasilkan, bukan ditulis.
 *
 *   2. Judul menamai bahan yang tidak ada — resep "Nasi goreng udang"
 *      bahannya tidak memuat udang. Paling umum dan paling terlihat mata.
 *
 *   3. Judul mustahil dengan bahannya — "Teh poci" berflour, resep manis
 *      berbawang. Ditangkap lewat kata kunci pada judul.
 */

use App\Models\Resep;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

/** Kata yang tidak dihitung sebagai nama bahan pada judul. */
const PENYAMBUNG = [
    'yang', 'dan', 'dengan', 'untuk', 'dari', 'dipakai', 'resep', 'khas', 'spesial',
    'asli', 'sederhana', 'bumbu', 'kuah', 'goreng', 'bakar', 'kuah', 'sambal',
    'pedas', 'manis', 'gurih', 'enak', 'legendar', 'rumahan', 'ala', 'isi', 'isi',
];

/**
 * Pola judul untuk mendeteksi resep berjudul minuman.
 *
 * Sengaja DIJANGKAR di awal dan dipakai di sini persis seperti pada
 * `perbaiki-bahan-minuman.php`, supaya kedua skrip menghitung jumlah yang
 * sama. Versi longgar yang memakai `str_contains` pernah dipakai sebentar dan
 * gagal: kata kunci `'es '` cocok dengan "Risoles Ragout", `'lalap'` cocok
 * dengan "Kalalapun" — daftarnya melompat dari 11 ke 17 resep.
 *
 * Minuman dicari lewat judul karena enum kategori tidak punya nilai
 * "minuman": "Teh poci gula batu" jatuh ke `jajanan` dan otomatis menerima
 * kerangka tepung terigu.
 */
const POLA_MINUMAN = '/^(es\s|teh\b|kopi\b|jus\b|cendol$|dawet$|sirup\s)/i';

/**
 * Hanya TEPUNG TERIGU yang jadi cacat pada minuman.
 *
 * Sengaja tidak mencakup "tepung" secara umum: tepung beras dan tepung
 * hunkwe adalah bahan asli cendol, dawet, dan pisang ijo. Menandai keduanya
 * sebagai keliru akan membuat skrip ini berteriak pada data yang benar —
 * persis yang terjadi sebelum aturannya dipersempit.
 */
const TEPUNG_TERIGU = 'terigu';

/** Kata judul yang tidak mungkin ada dalam kelompok bahan ini. */
const KONTRADIKSI = [
    'manis' => ['bawang', 'cabai', 'santan kental'],
    'teh' => ['tepung', 'terigu', 'bawang', 'cabai', 'daging'],
    'kopi' => ['tepung', 'terigu', 'bawang', 'cabai', 'daging'],
];

function namaBahan($bahan): array
{
    $hasil = [];

    foreach ((array) $bahan as $item) {
        if (is_array($item)) {
            // Bisa berupa list terindeks [nama, jumlah, satuan] atau assoc.
            $nama = $item['nama'] ?? $item[0] ?? null;
        } else {
            $nama = $item;
        }

        if (is_string($nama) && $nama !== '') {
            $hasil[] = mb_strtolower(trim($nama));
        }
    }

    return array_values(array_unique($hasil));
}

function bermakna(string $judul): array
{
    $kata = preg_split('/[^a-z]+/i', mb_strtolower($judul), -1, PREG_SPLIT_NO_EMPTY) ?: [];

    return array_values(array_filter(
        $kata,
        fn (string $k) => mb_strlen($k) >= 4 && ! in_array($k, PENYAMBUNG, true),
    ));
}

$resep = Resep::query()->get(['judul', 'slug', 'kategori', 'bahan']);
$total = $resep->count();

echo "==========================================================\n";
echo "1. BUKTI TEMPLATE — varian set nama bahan per kategori\n";
echo "==========================================================\n\n";

$perKategori = $resep
    ->groupBy(fn (Resep $r) => $r->kategori->value)
    ->sortByDesc(fn ($g) => $g->count());

foreach ($perKategori as $kategori => $group) {
    $varian = $group
        ->groupBy(fn (Resep $r) => implode('|', namaBahan($r->bahan)))
        ->sortByDesc(fn ($g) => $g->count());

    printf("%-14s %3d resep  →  %d varian bahan\n", $kategori, $group->count(), $varian->count());

    foreach ($varian as $isi => $anggota) {
        $nama = explode('|', $isi);
        printf(
            "    %3d resep : %s\n",
            $anggota->count(),
            implode(', ', array_map(fn ($n) => ucwords($n), $nama)),
        );
    }
}

echo "\n==========================================================\n";
echo "2. JUDUL MENAMAI BAHAN YANG TIDAK ADA\n";
echo "==========================================================\n\n";

$hilang = $resep->filter(function (Resep $r) {
    $kata = bermakna($r->judul);

    if ($kata === []) {
        return false;
    }

    $isi = implode(' ', namaBahan($r->bahan));

    // Judul dianggap menamai bahan kalau ADA kata berarti yang juga
    // seharusnya muncul di daftar bahan — misalnya "udang" pada "Nasi
    // goreng udang". Yang dicari adalah ketidakhadiran.
    return ! array_filter($kata, fn (string $k) => str_contains($isi, $k));
});

printf("  %d dari %d resep (%.0f%%) — judulnya tidak menyebut SATU PUN\n", $hilang->count(), $total, 100 * $hilang->count() / $total);
printf("  bahan yang cocok dengan judulnya sendiri.\n\n");

$hilang->take(10)->each(
    fn (Resep $r) => printf("  · %-30s bahan: %s\n", $r->judul, implode(', ', namaBahan($r->bahan))),
);

echo "\n==========================================================\n";
echo "3. JUDUL MUSTAHIL DENGAN BAHANNYA\n";
echo "==========================================================\n\n";

$minuman = $resep->filter(
    fn (Resep $r) => preg_match(POLA_MINUMAN, trim($r->judul)) === 1,
);

printf("  Resep berjudul minuman: %d\n", $minuman->count());

foreach ($minuman as $r) {
    $terigu = array_filter(
        namaBahan($r->bahan),
        fn (string $n) => str_contains($n, TEPUNG_TERIGU),
    );
    printf(
        "    %s%s\n",
        str_pad($r->judul, 34),
        $terigu !== [] ? '⚠ tepung terigu: '.implode(', ', $terigu) : '—',
    );
}

$kopiTeh = $resep->filter(fn (Resep $r) => preg_match('/\bkopi\b|\bteh\b/i', $r->judul));
printf("\n  Resep kopi/teh: %d dari %d\n", $kopiTeh->count(), $total);
printf("  Semuanya kategori: %s\n", $kopiTeh->pluck('kategori.value')->unique()->implode(', '));

echo "\n  Kontradiksi judul ↔ bahan:\n";
foreach (KONTRADIKSI as $kunci => $dilarang) {
    $ketemu = $resep->filter(function (Resep $r) use ($kunci, $dilarang) {
        if (! str_contains(mb_strtolower($r->judul), $kunci)) {
            return false;
        }

        $isi = implode(' ', namaBahan($r->bahan));

        return array_filter($dilarang, fn (string $k) => str_contains($isi, $k)) !== [];
    });

    printf("    judul mengandung \"%s\" + bahan terlarang: %d resep\n", $kunci, $ketemu->count());
    $ketemu->take(3)->each(fn (Resep $r) => printf("        · %s\n", $r->judul));
}
