<?php

/**
 * Mengganti kerangka bahan pada resep-resep berjudul minuman.
 *
 *   php scripts/perbaiki-bahan-minuman.php            # scan, tidak menulis
 *   php scripts/perbaiki-bahan-minuman.php --terapkan  # tulis + backup
 *
 * Kenapa 11 resep ini khusus: enum kategori tidak punya nilai "minuman",
 * sehingga Es campur, Cendol, Dawet, dan Teh poci gula batu jatuh ke `jajanan`
 * dan otomatis menerima kerangka yang sama dengan jajanan gorengan — tepung
 * terigu, bawang merah, cabai merah, serai, minyak goreng. Hasilnya "Es
 * campur" berisi 431 gram tepung terigu dan "Teh poci gula batu" sama
 * persis. Jauh lebih mencolok daripada kategori lain yang masih masuk akal
 * secara kasar.
 *
 * Dua pemeriksaan menahan tulisan selamanya:
 *   1. semua slug tujuan harus ada, dan tidak boleh ada resep berjudul
 *      minuman yang terlewat dari daftar;
 *   2. tidak satu pun bahan pengganti yang berisi entri jajanan terlarang.
 */

use App\Models\Resep;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

/**
 * Bahan yang TIDAK PERNAH sah dalam sebuah minuman, apa pun jenisnya.
 * Garam dan gula sengaja tidak masuk — keduanya wajar dalam minuman.
 */
const TERLARANG = ['Tepung terigu', 'Bawang merah', 'Bawang putih', 'Cabai merah', 'Minyak goreng', 'Serai'];

/**
 * Pola judul untuk mendeteksi minuman yang terlewat. Dijaga longgar: tugasnya
 * menolak tulisan, bukan menentukan isi.
 */
const POLA_MINUMAN = '/^(es\s|teh\b|kopi\b|jus\b|cendol$|dawet$|sirup\s)/i';

/** @var array<string, array{bahan: array<int, array{nama: string, jumlah: int|float, satuan: string}>, langkah: array<int, string>}> */
const PENGGANTI = [
    'es-campur' => [
        'bahan' => [
            ['nama' => 'Sirup cocopandan', 'jumlah' => 150, 'satuan' => 'ml'],
            ['nama' => 'Santan kental', 'jumlah' => 200, 'satuan' => 'ml'],
            ['nama' => 'Kelapa muda', 'jumlah' => 150, 'satuan' => 'gram'],
            ['nama' => 'Alpukat', 'jumlah' => 1, 'satuan' => 'buah'],
            ['nama' => 'Kolang kaling', 'jumlah' => 100, 'satuan' => 'gram'],
            ['nama' => 'Cendol', 'jumlah' => 100, 'satuan' => 'gram'],
            ['nama' => 'Gula merah', 'jumlah' => 80, 'satuan' => 'gram'],
            ['nama' => 'Es serut', 'jumlah' => 300, 'satuan' => 'gram'],
        ],
        'langkah' => [
            'Rebus gula merah bersama 100 ml air sampai larut, sisihkan sampai dingin.',
            'Siapkan mangkuk saji, tata kelapa muda, alpukat, kolang kaling, dan cendol.',
            'Tuang sirup cocopandan dan santan kental di atasnya.',
            'Tambahkan es serut, lalu siram dengan larutan gula merah sebelum dihidangkan.',
        ],
    ],
    'es-doger' => [
        'bahan' => [
            ['nama' => 'Santan kental', 'jumlah' => 250, 'satuan' => 'ml'],
            ['nama' => 'Gula merah', 'jumlah' => 100, 'satuan' => 'gram'],
            ['nama' => 'Alpukat', 'jumlah' => 1, 'satuan' => 'buah'],
            ['nama' => 'Pacar cendol', 'jumlah' => 100, 'satuan' => 'gram'],
            ['nama' => 'Ketan hitam', 'jumlah' => 80, 'satuan' => 'gram'],
            ['nama' => 'Kelapa muda', 'jumlah' => 100, 'satuan' => 'gram'],
            ['nama' => 'Sirup cocopandan', 'jumlah' => 60, 'satuan' => 'ml'],
            ['nama' => 'Es serut', 'jumlah' => 300, 'satuan' => 'gram'],
        ],
        'langkah' => [
            'Rebus gula merah dengan 100 ml air hingga larut, lalu biarkan dingin.',
            'Kukus ketan hitam sampai matang pulen, angin-anginkan.',
            'Susun ketan hitam, pacar cendol, dan kelapa muda dalam mangkuk.',
            'Tuang santan, sirup, dan larutan gula merah, tutup dengan es serut.',
        ],
    ],
    'es-kelapa-muda' => [
        'bahan' => [
            ['nama' => 'Kelapa muda', 'jumlah' => 1, 'satuan' => 'buah'],
            ['nama' => 'Air kelapa', 'jumlah' => 300, 'satuan' => 'ml'],
            ['nama' => 'Gula cair', 'jumlah' => 60, 'satuan' => 'ml'],
            ['nama' => 'Jeruk nipis', 'jumlah' => 1, 'satuan' => 'buah'],
            ['nama' => 'Selasih', 'jumlah' => 1, 'satuan' => 'sdt'],
            ['nama' => 'Es batu', 'jumlah' => 250, 'satuan' => 'gram'],
            ['nama' => 'Daun mint', 'jumlah' => 3, 'satuan' => 'lembar'],
        ],
        'langkah' => [
            'Ambil daging kelapa muda dengan sendok, sisihkan bersama airnya.',
            'Rendam selasih dalam air dingin sampai mengembang, sekitar 10 menit.',
            'Campur air kelapa, gula cair, dan perasan jeruk nipis.',
            'Masukkan es batu dan daging kelapa, taburi selasih serta daun mint.',
        ],
    ],
    'es-kepal' => [
        'bahan' => [
            ['nama' => 'Gula aren', 'jumlah' => 150, 'satuan' => 'gram'],
            ['nama' => 'Air', 'jumlah' => 250, 'satuan' => 'ml'],
            ['nama' => 'Susu cair', 'jumlah' => 400, 'satuan' => 'ml'],
            ['nama' => 'Cokelat bubuk', 'jumlah' => 2, 'satuan' => 'sdm'],
            ['nama' => 'Krim bubuk', 'jumlah' => 2, 'satuan' => 'sdm'],
            ['nama' => 'Garam', 'jumlah' => 0.25, 'satuan' => 'sdt'],
            ['nama' => 'Es serut', 'jumlah' => 300, 'satuan' => 'gram'],
            ['nama' => 'Kacang sangrai', 'jumlah' => 50, 'satuan' => 'gram'],
        ],
        'langkah' => [
            'Masak gula aren dengan 100 ml air sampai kental dan berwarna gelap.',
            'Aduk susu, cokelat bubuk, krim bubuk, dan garam sampai tidak bergerindil.',
            'Tuang susu ke atas es serut yang sudah ditata dalam gelas.',
            'Siram kuah gula aren di atasnya, taburi kacang sangrai sebelum disajikan.',
        ],
    ],
    'es-teler' => [
        'bahan' => [
            ['nama' => 'Santan kental', 'jumlah' => 200, 'satuan' => 'ml'],
            ['nama' => 'Alpukat', 'jumlah' => 1, 'satuan' => 'buah'],
            ['nama' => 'Nangka', 'jumlah' => 100, 'satuan' => 'gram'],
            ['nama' => 'Kelapa muda', 'jumlah' => 100, 'satuan' => 'gram'],
            ['nama' => 'Sirup cocopandan', 'jumlah' => 60, 'satuan' => 'ml'],
            ['nama' => 'Gula pasir', 'jumlah' => 80, 'satuan' => 'gram'],
            ['nama' => 'Susu kental manis', 'jumlah' => 100, 'satuan' => 'ml'],
            ['nama' => 'Es serut', 'jumlah' => 300, 'satuan' => 'gram'],
        ],
        'langkah' => [
            'Rebus santan bersama gula pasir dan sejumput garam sampai mendidih, dinginkan.',
            'Iris alpukat dan nangka memanjang, serut kelapa muda.',
            'Tata alpukat, nangka, dan kelapa muda di atas es serut.',
            'Siram santan manis, tuang sirup cocopandan dan susu kental manis di atasnya.',
        ],
    ],
    'cendol' => [
        'bahan' => [
            ['nama' => 'Tepung beras', 'jumlah' => 150, 'satuan' => 'gram'],
            ['nama' => 'Tepung hunkwe', 'jumlah' => 50, 'satuan' => 'gram'],
            ['nama' => 'Air daun pandan', 'jumlah' => 400, 'satuan' => 'ml'],
            ['nama' => 'Gula merah', 'jumlah' => 150, 'satuan' => 'gram'],
            ['nama' => 'Santan kental', 'jumlah' => 300, 'satuan' => 'ml'],
            ['nama' => 'Garam', 'jumlah' => 0.5, 'satuan' => 'sdt'],
            ['nama' => 'Es serut', 'jumlah' => 300, 'satuan' => 'gram'],
            ['nama' => 'Daun pandan', 'jumlah' => 3, 'satuan' => 'lembar'],
        ],
        'langkah' => [
            'Campur tepung beras, tepung hunkwe, dan sebagian air daun pandan sampai halus.',
            'Masak sisa air pandan bersama daun pandan, tuang adonan sambil terus mengaduk sampai kental.',
            'Cetak adonan ke dalam air es sampai menggumpal, tiriskan.',
            'Rebus gula merah dengan sedikit air untuk kuah, lalu santan dengan garam untuk saus.',
            'Sajikan cendol dengan es serut, kuah gula merah, dan santan.',
        ],
    ],
    'dawet' => [
        'bahan' => [
            ['nama' => 'Tepung beras', 'jumlah' => 120, 'satuan' => 'gram'],
            ['nama' => 'Tepung hunkwe', 'jumlah' => 40, 'satuan' => 'gram'],
            ['nama' => 'Air daun suji', 'jumlah' => 400, 'satuan' => 'ml'],
            ['nama' => 'Gula merah', 'jumlah' => 150, 'satuan' => 'gram'],
            ['nama' => 'Santan kental', 'jumlah' => 300, 'satuan' => 'ml'],
            ['nama' => 'Garam', 'jumlah' => 0.5, 'satuan' => 'sdt'],
            ['nama' => 'Es serut', 'jumlah' => 300, 'satuan' => 'gram'],
            ['nama' => 'Daun suji', 'jumlah' => 5, 'satuan' => 'lembar'],
        ],
        'langkah' => [
            'Rebus air daun suji bersama daun suji, angkat setelah warnanya keluar.',
            'Campur tepung beras dan tepung hunkwe dengan air suji sampai licin.',
            'Masak adonan sambil diaduk sampai mengental dan berkilap.',
            'Cetak ke dalam air es, diamkan sampai dawet mengapung lalu tiriskan.',
            'Sajikan dengan es serut, kuah gula merah, dan santan rebus bergaram.',
        ],
    ],
    'teh-poci-gula-batu' => [
        'bahan' => [
            ['nama' => 'Teh celup poci', 'jumlah' => 4, 'satuan' => 'kantong'],
            ['nama' => 'Gula batu', 'jumlah' => 150, 'satuan' => 'gram'],
            ['nama' => 'Air panas', 'jumlah' => 700, 'satuan' => 'ml'],
            ['nama' => 'Gula merah', 'jumlah' => 50, 'satuan' => 'gram'],
            ['nama' => 'Jeruk nipis', 'jumlah' => 1, 'satuan' => 'buah'],
            ['nama' => 'Es batu', 'jumlah' => 300, 'satuan' => 'gram'],
            ['nama' => 'Daun pandan', 'jumlah' => 2, 'satuan' => 'lembar'],
        ],
        'langkah' => [
            'Seduh teh celup poci dengan air panas selama 3 menit, angkat kantongnya.',
            'Rebus gula merah dan daun pandan dengan 100 ml air sampai larut.',
            'Tuang teh ke gelas berisi gula batu, aduk sampai gula larut sebagian.',
            'Tambahkan larutan gula merah dan perasan jeruk nipis.',
            'Masukkan es batu, sajikan segera.',
        ],
    ],
    'es-oyen' => [
        'bahan' => [
            ['nama' => 'Kelapa muda', 'jumlah' => 150, 'satuan' => 'gram'],
            ['nama' => 'Alpukat', 'jumlah' => 1, 'satuan' => 'buah'],
            ['nama' => 'Nangka', 'jumlah' => 100, 'satuan' => 'gram'],
            ['nama' => 'Kolang kaling', 'jumlah' => 100, 'satuan' => 'gram'],
            ['nama' => 'Sirup cocopandan', 'jumlah' => 80, 'satuan' => 'ml'],
            ['nama' => 'Susu kental manis', 'jumlah' => 100, 'satuan' => 'ml'],
            ['nama' => 'Gula merah', 'jumlah' => 80, 'satuan' => 'gram'],
            ['nama' => 'Es serut', 'jumlah' => 300, 'satuan' => 'gram'],
        ],
        'langkah' => [
            'Rebus gula merah dengan 100 ml air sampai larut, dinginkan.',
            'Potong alpukat dan nangka, serut kelapa muda, tiriskan kolang kaling.',
            'Tata semua buah di dalam mangkuk berisi es serut.',
            'Siram gula merah, sirup cocopandan, dan susu kental manis di atasnya.',
        ],
    ],
    'es-buah' => [
        'bahan' => [
            ['nama' => 'Semangka', 'jumlah' => 200, 'satuan' => 'gram'],
            ['nama' => 'Melon', 'jumlah' => 150, 'satuan' => 'gram'],
            ['nama' => 'Nangka', 'jumlah' => 80, 'satuan' => 'gram'],
            ['nama' => 'Alpukat', 'jumlah' => 1, 'satuan' => 'buah'],
            ['nama' => 'Sirup cocopandan', 'jumlah' => 80, 'satuan' => 'ml'],
            ['nama' => 'Susu kental manis', 'jumlah' => 100, 'satuan' => 'ml'],
            ['nama' => 'Gula cair', 'jumlah' => 60, 'satuan' => 'ml'],
            ['nama' => 'Es serut', 'jumlah' => 300, 'satuan' => 'gram'],
        ],
        'langkah' => [
            'Potong semangka, melon, dan nangka dadu, iris alpukat memanjang.',
            'Tata semua buah di atas es serut dalam mangkuk saji.',
            'Tuang gula cair dan sirup cocopandan di atasnya.',
            'Tutup dengan susu kental manis, sajikan langsung.',
        ],
    ],
    'es-pisang-ijo' => [
        'bahan' => [
            ['nama' => 'Pisang raja', 'jumlah' => 6, 'satuan' => 'buah'],
            ['nama' => 'Tepung beras', 'jumlah' => 150, 'satuan' => 'gram'],
            ['nama' => 'Santan kental', 'jumlah' => 300, 'satuan' => 'ml'],
            ['nama' => 'Gula merah', 'jumlah' => 150, 'satuan' => 'gram'],
            ['nama' => 'Garam', 'jumlah' => 0.5, 'satuan' => 'sdt'],
            ['nama' => 'Daun pandan', 'jumlah' => 3, 'satuan' => 'lembar'],
            ['nama' => 'Es serut', 'jumlah' => 300, 'satuan' => 'gram'],
            ['nama' => 'Sirup cocopandan', 'jumlah' => 60, 'satuan' => 'ml'],
        ],
        'langkah' => [
            'Rebus santan bersama daun pandan, garam, dan tepung beras sampai mengental.',
            'Bungkus tiap pisang dengan aduk santan di atas daun pisang, kukus 20 menit.',
            'Rebus gula merah dengan 100 ml air untuk kuah karamel.',
            'Iris pisang ijo, sajikan di atas es serut dengan kuah gula merah dan sirup.',
        ],
    ],
];

$terapkan = in_array('--terapkan', $argv ?? [], true);

$resep = Resep::query()
    ->get(['judul', 'slug', 'kategori', 'bahan', 'langkah'])
    ->keyBy('slug');

$minumanDitemukan = $resep->filter(
    fn (Resep $r) => preg_match(POLA_MINUMAN, trim($r->judul)) === 1,
);

$gagal = [];

// ── 1. Semua slug tujuan ada ──
$hilang = array_diff(array_keys(PENGGANTI), $resep->keys()->all());
if ($hilang !== []) {
    $gagal[] = 'slug tidak ditemukan di database: '.implode(', ', $hilang);
}

// ── 2. Tidak ada resep berjudul minuman yang terlewat ──
$terlewat = $minumanDitemukan
    ->keys()
    ->diff(array_keys(PENGGANTI))
    ->values();

if ($terlewat->isNotEmpty()) {
    $gagal[] = 'minuman terlewat: '.implode(', ', $terlewat->map(fn ($s) => $resep[$s]->judul)->all());
}

$extra = collect(PENGGANTI)->keys()->diff($minumanDitemukan->keys());
if ($extra->isNotEmpty()) {
    $gagal[] = 'slug di PENGGANTI bukan minuman menurut POLA_MINUMAN: '.implode(', ', $extra);
}

// ── 3. Struktur bahan pengganti valid ──
foreach (PENGGANTI as $slug => $isi) {
    foreach ($isi['bahan'] as $i => $item) {
        foreach (['nama', 'jumlah', 'satuan'] as $kunci) {
            if (! array_key_exists($kunci, $item)) {
                $gagal[] = "{$slug} bahan[{$i}] tidak punya kunci {$kunci}";
            }
        }
    }

    if (count($isi['bahan']) < 3) {
        $gagal[] = "{$slug} cuma punya ".count($isi['bahan']).' bahan';
    }

    if ($isi['langkah'] === []) {
        $gagal[] = "{$slug} tidak punya langkah";
    }
}

// ── 4. Tidak ada bahan minuman yang masih berisi entri jajanan ──
foreach (PENGGANTI as $slug => $isi) {
    $nama = collect($isi['bahan'])->pluck('nama')->map(fn ($n) => mb_strtolower($n));
    foreach (TERLARANG as $dilarang) {
        if ($nama->contains(mb_strtolower($dilarang))) {
            $gagal[] = "{$slug} masih memuat bahan terlarang: {$dilarang}";
        }
    }
}

// ── Laporan ──
echo "=========================================================\n";
echo "RESEP BERJUDUL MINUMAN — scan\n";
echo "=========================================================\n\n";

foreach ($minumanDitemukan as $slug => $r) {
    $ada = isset(PENGGANTI[$slug]);
    $lama = collect($r->bahan)->pluck('nama')->implode(', ');

    printf("%s %-26s [%s]\n", $ada ? '✓' : '✗', $r->judul, $r->kategori->value);
    printf("    lama: %s\n", $lama);
    printf("    langkah pertama: %s\n", mb_substr($r->langkah[0] ?? '(kosong)', 0, 90));

    if ($ada) {
        printf("    baru: %s\n", collect(PENGGANTI[$slug]['bahan'])->pluck('nama')->implode(', '));
    }

    echo "\n";
}

printf("Total: %d minuman, %d punya pengganti\n", $minumanDitemukan->count(), count(PENGGANTI));

if ($gagal !== []) {
    echo "\nDITOLAK — pemeriksaan gagal:\n";
    foreach ($gagal as $g) {
        echo "  · {$g}\n";
    }

    exit(1);
}

echo "\nSemua pemeriksaan lolos.\n";

if (! $terapkan) {
    echo "Mode scan — belum ada yang ditulis. Tambah --terapkan untuk menulis.\n";

    exit(0);
}

// ── Tulis ──
$backup = __DIR__.'/../storage/framework/bahan-minuman-lama.json';

file_put_contents(
    $backup,
    json_encode(
        $resep
            ->only(array_keys(PENGGANTI))
            ->map(fn (Resep $r) => ['judul' => $r->judul, 'bahan' => $r->bahan, 'langkah' => $r->langkah])
            ->all(),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
    ),
);

$tulis = 0;

foreach (PENGGANTI as $slug => $isi) {
    Resep::query()->where('slug', $slug)->update([
        'bahan' => $isi['bahan'],
        'langkah' => $isi['langkah'],
    ]);

    $tulis += 1;
}

printf("\n%d resep diperbarui.\n", $tulis);
printf("Backup: %s\n", str_replace(__DIR__.'/../', '', $backup));
