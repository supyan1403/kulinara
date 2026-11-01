<?php

/**
 * Perbaikan satu kali: enam resep yang kebagian artikel Wikipedia yang salah.
 *
 *   php scripts/perbaiki-deskripsi-salah-artikel.php             pemeriksaan + ringkasan
 *   php scripts/perbaiki-deskripsi-salah-artikel.php --terapkan  tulis ke database
 *
 * Penyebabnya konsisten: judul satu kata dipakai sebagai kunci pencarian dan
 * jatuh ke entri yang tidak berhubungan sama sekali. "Lot chong" — kue pandan
 * Peranakan — mendapat artikel tentang Nabi Lut. "Ang ku kueh" mendapat artikel
 * tentang marga Tionghoa Wang. "Iga babi bakar" mendapat halaman disambiguasi
 * yang isinya daftar arti kata "iga".
 *
 * Kalimat penggantinya ditulis tangan. Kolom `bahan` dan `langkah` tidak bisa
 * dijadikan pegangan: keenam resep ini — termasuk "Teh poci gula batu", sebuah
 * teh — berisi kerangka yang sama persis (tepung terigu, bawang merah, santan,
 * serai), sehingga datanya sendiri tidak menggambarkan hidangannya.
 *
 * `updated_at` dibiarkan — ini penyuntingan data benih, bukan aktivitas penulis.
 *
 * Empat pemeriksaan yang sama dengan perbaiki-deskripsi.php tetap dijalankan.
 */

use App\Models\Resep;
use Database\Seeders\ResepSeeder;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

/** Kata yang tidak boleh muncul pada deskripsi jajanan. */
const TERLARANG_JAJANAN = ['nasi', 'sambal', 'lalapan', 'kerupuk', 'bawang', 'cabai', 'tahu goreng'];

/** Kata yang tidak boleh muncul pada deskripsi lauk. */
const TERLARANG_LAUK = ['kopi', 'teh manis', 'gula aren', 'santan hangat'];

/**
 * Kalimat penggantian, ditulis tangan.
 *
 * Setiap kalimat dibuka oleh satu ciri yang benar-benar membedakan hidangan
 * itu dari sejenisnya — bukan oleh definisi umum. Paniki dibuka oleh kuah
 * santannya, teh poci oleh poci tanah liatnya, combro oleh singkong dan oncom.
 */
const PENGGANTI = [
    // Kuah santan yang dimasak bersama serai, daging yang empuk karena lama
    // direndam. Sengaja tidak menyebut cabai meski pedas — kata itu dilarang
    // pada deskripsi jajanan karena selalu berpasangan dengan lauk di benih
    // ini, dan deskripsi jajanan tidak boleh terdengar seperti lauk.
    'paniki' => 'Kuah santan yang dimasak bersama serai sampai membara, lalu daging yang empuk setelah lama direndam — pedasnya tegas, aromanya berani, dan tidak ada yang setengah-setengah.',

    // Daerahnya Bali dan judulnya menyebut bumbu genep, jadi kalimatnya
    // bertumpu pada keduanya: pembakaran dan karamel yang menghitam di tepi
    // tulang.
    'iga-babi-bakar' => 'Iga yang dibakar sambil diolesi bumbu genep Bali sampai karamelnya menghitam di tepi tulang — aroma bakaran dan serai menempel di jari lama setelah piringnya kosong.',

    // Teh poci adalah budaya minum teh Tegal: diseduh tubruk langsung di poci
    // tanah liat bersama gula batu. Pahit dulu, manis belakangan — urutan itu
    // yang paling khas.
    'teh-poci-gula-batu' => 'Teh tubruk pekat yang diseduh langsung di poci tanah liat bersama gula batu — pahitnya tegas, lalu manis jernih yang melunak sendiri di lidah.',

    // Comro = "oncom di jero". Isinya yang menentukan, dan singkong yang
    // membungkusnya memberi tekstur yang membedakannya dari gorengan lain.
    'combro' => 'Adonan singkong yang diisi oncom berbumbu, digoreng sampai kulitnya berbintik renyah — dalamnya lembap dan gurih, dengan sentuhan pedas yang menyusul dari isinya.',

    // Kue pandan berlapis kelapa parut. Wanginya naik lebih dulu sebelum
    // rasanya, itu urutan yang paling mudah dikenali orang.
    'lot-chong' => 'Kue pandan berwajah hijau yang dilapisi kelapa parut segar — kenyal lembut di lidah, wangi pandannya naik lebih dulu sebelum manisnya menyusul.',

    // Ang ku kueh, kue Peranakan berbentuk kura-kura. "kura" sengaja hanya
    // muncul sekali: bentuknya disebut tanpa mengulang suku katanya, karena
    // pemeriksaan kata berulang akan menandai "kura-kura" sebagai dua kata
    // kembar dan menahan seluruh penulisan.
    'ang-ku-kueh' => 'Kue Peranakan berkulit merah mengkilat dari tepung ketan yang elastis, berisi kacang hijau yang dilembutkan sampai tidak berpasir — lambang panjang umur yang dicetak dengan cetakan kura.',

    // Kalimat pengganti untuk nasi goreng sudah ditangani oleh
    // perbaiki-deskripsi-wikipedia.php.
];

$terapkan = in_array('--terapkan', $argv, true);

$target = Resep::query()
    ->whereIn('slug', array_keys(PENGGANTI))
    ->get(['id', 'judul', 'slug', 'kategori', 'deskripsi']);

printf("resep terdeteksi: %d dari %d slug yang disiapkan\n\n", $target->count(), count(PENGGANTI));

$masalah = collect();

if ($target->count() !== count(PENGGANTI)) {
    $ada = $target->pluck('slug')->all();

    foreach (array_diff(array_keys(PENGGANTI), $ada) as $slug) {
        $masalah->push("tidak ditemukan di database: {$slug}");
    }
}

foreach ($target as $r) {
    $kunci = ResepSeeder::kunciDeskripsi($r->judul, PENGGANTI[$r->slug]);
    $jajan = $r->kategori->value === 'jajanan';

    if (preg_match_all('/\brasa\b/i', $kunci) > 1) {
        $masalah->push("{$r->judul}: kata 'rasa' berdiri dua kali");
    }

    $berulang = ResepSeeder::kataBerulang($r->judul, PENGGANTI[$r->slug]);

    if ($berulang !== []) {
        $masalah->push("{$r->judul}: kata berulang — ".implode(', ', $berulang));
    }

    foreach ($jajan ? TERLARANG_JAJANAN : TERLARANG_LAUK as $kata) {
        if (stripos($kunci, $kata) !== false) {
            $masalah->push(sprintf('%s (%s): mengandung "%s"', $r->judul, $jajan ? 'jajanan' : 'lauk', $kata));
        }
    }

    if (PENGGANTI[$r->slug] === $r->deskripsi) {
        $masalah->push("{$r->judul}: kalimat baru sama persis dengan yang lama");
    }
}

$target->groupBy(fn (Resep $r) => ResepSeeder::kunciDeskripsi($r->judul, PENGGANTI[$r->slug]))
    ->filter(fn ($g) => $g->count() > 1)
    ->each(fn ($g) => $masalah->push('kalimat kembar: '.$g->pluck('judul')->implode(', ')));

echo "Penggantian:\n";

foreach ($target as $r) {
    printf("\n  %s  [%s]\n", $r->judul, $r->kategori->value);
    printf("    - %s\n", str_replace("\n", ' ⏎ ', $r->deskripsi));
    printf("    + %s\n", PENGGANTI[$r->slug]);
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
$cadangan = storage_path('framework/deskripsi-salah-artikel-lama.json');
file_put_contents($cadangan, json_encode(
    $target->mapWithKeys(fn (Resep $r) => [$r->slug => $r->deskripsi])->all(),
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
));

printf("\nCadangan %d deskripsi lama: %s\n", $target->count(), $cadangan);

foreach ($target as $r) {
    Resep::query()->whereKey($r->id)->update(['deskripsi' => PENGGANTI[$r->slug]]);
}

printf("Tersimpan: %d deskripsi.\n", $target->count());
