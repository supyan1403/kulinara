<?php

namespace Database\Seeders;

use App\Enums\KategoriResep;
use App\Enums\StatusResep;
use App\Enums\TingkatKesulitan;
use App\Enums\UserRole;
use App\Models\Resep;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResepSeeder extends Seeder
{
    /**
     * Resep kurasi — punya bahan & langkah lengkap, plus foto asli
     * yang diunduh dari desain Stitch.
     *
     * @var array<int, array<string, mixed>>
     */
    private const KURASI = [
        [
            'judul' => 'Pepes Ikan Mas Daun Kemangi',
            'kategori' => KategoriResep::IkanSeafood,
            'daerah' => 'Jawa Barat',
            'waktu' => 60,
            'porsi' => 4,
            'kesulitan' => TingkatKesulitan::Sedang,
            'gambar' => 'pepes-ikan-mas.jpg',
            'deskripsi' => 'Ikan mas dibungkus daun pisang bersama kemangi dan bumbu kuning, dikukus sampai aroma rempahnya meresap penuh ke dalam daging ikan.',
            'energi' => 265, 'protein' => 29.5, 'lemak' => 11.2, 'karbo' => 9.4,
            'bahan' => [
                ['nama' => 'Ikan mas segar', 'jumlah' => 800, 'satuan' => 'gram'],
                ['nama' => 'Daun kemangi', 'jumlah' => 2, 'satuan' => 'ikat'],
                ['nama' => 'Daun pisang untuk membungkus', 'jumlah' => 8, 'satuan' => 'lembar'],
                ['nama' => 'Bawang merah', 'jumlah' => 10, 'satuan' => 'siung'],
                ['nama' => 'Bawang putih', 'jumlah' => 5, 'satuan' => 'siung'],
                ['nama' => 'Kunyit', 'jumlah' => 3, 'satuan' => 'ruas'],
                ['nama' => 'Kemiri', 'jumlah' => 4, 'satuan' => 'butir'],
                ['nama' => 'Cabai merah keriting', 'jumlah' => 8, 'satuan' => 'buah'],
                ['nama' => 'Tomat', 'jumlah' => 2, 'satuan' => 'buah'],
                ['nama' => 'Garam', 'jumlah' => 1.5, 'satuan' => 'sdt'],
            ],
            'langkah' => [
                'Bersihkan ikan mas, buang sisik dan insang, cuci sampai tidak berlendir.',
                'Haluskan bawang merah, bawang putih, kunyit, kemiri, dan cabai keriting.',
                'Balurkan bumbu halus ke seluruh permukaan ikan, diamkan 20 menit agar meresap.',
                'Letakkan daun pisang, tata kemangi, tomato, lalu letakkan ikan di atasnya.',
                'Bungkus rapat dengan teknik lipat korek api, sematkan lidi di ujungnya.',
                'Kukus 35 menit dengan api sedang sampai matang dan harum.',
                'Bakar sebentar di atas teflon untuk memperkuat aroma daun pisang.',
            ],
        ],
        [
            'judul' => 'Risoles Ragout Ayam Kampung',
            'kategori' => KategoriResep::Jajanan,
            'daerah' => 'Jawa',
            'waktu' => 45,
            'porsi' => 4,
            'kesulitan' => TingkatKesulitan::Sedang,
            'gambar' => 'risoles-ragout.jpg',
            'deskripsi' => 'Kulit lembut berselimut panir renyah dengan isian suwiran ragout wortel dan suwiran ayam kampung yang gurih.',
            'energi' => 210, 'protein' => 9.0, 'lemak' => 11.5, 'karbo' => 19.8,
            'bahan' => [
                ['nama' => 'Daging ayam kampung', 'jumlah' => 350, 'satuan' => 'gram'],
                ['nama' => 'Wortel', 'jumlah' => 2, 'satuan' => 'buah'],
                ['nama' => 'Tepung terigu', 'jumlah' => 200, 'satuan' => 'gram'],
                ['nama' => 'Susu cair', 'jumlah' => 300, 'satuan' => 'ml'],
                ['nama' => 'Bawang bombay', 'jumlah' => 1, 'satuan' => 'buah'],
                ['nama' => 'Tepung panir', 'jumlah' => 150, 'satuan' => 'gram'],
                ['nama' => 'Telur', 'jumlah' => 2, 'satuan' => 'butir'],
                ['nama' => 'Pala bubuk', 'jumlah' => 0.5, 'satuan' => 'sdt'],
            ],
            'langkah' => [
                'Rebus ayam sampai matang, suwir dagingnya sisihkan.',
                'Tumis bawang bombay sampai harum, masukkan wortel yang dipotong dadu kecil.',
                'Tuang susu cair sedikit demi sedikit sambil diaduk supaya tidak menggumpal.',
                'Masukkan ayam suwir, pala, garam, dan merica. Masak sampai kental lalu dinginkan.',
                'Buat adonan kulit dari tepung terigu dan susu, dadar tipis di teflon.',
                'Isi kulit dengan ragout, lipat rapi, celup ke telur lalu gulingkan ke panir.',
                'Goreng dengan minyak panas sampai keemasan, tiriskan sebelum disajikan.',
            ],
        ],
        [
            'judul' => 'Rendang Daging Sapi Gandik',
            'kategori' => KategoriResep::DagingSapi,
            'daerah' => 'Minangkabau',
            'waktu' => 180,
            'porsi' => 6,
            'kesulitan' => TingkatKesulitan::Sulit,
            'gambar' => 'rendang.jpg',
            'deskripsi' => 'Karamelisasi santan kental berempah pekat khas Payakumbuh yang empuk meresap hingga ke serat daging gandik.',
            'energi' => 420, 'protein' => 28.0, 'lemak' => 26.4, 'karbo' => 12.1,
            'bahan' => [
                ['nama' => 'Daging sapi gandik', 'jumlah' => 1000, 'satuan' => 'gram'],
                ['nama' => 'Santan kental', 'jumlah' => 2000, 'satuan' => 'ml'],
                ['nama' => 'Serai', 'jumlah' => 3, 'satuan' => 'batang'],
                ['nama' => 'Daun jeruk', 'jumlah' => 6, 'satuan' => 'lembar'],
                ['nama' => 'Daun kunyit', 'jumlah' => 1, 'satuan' => 'lembar'],
                ['nama' => 'Kemiri', 'jumlah' => 8, 'satuan' => 'butir'],
                ['nama' => 'Cabai merah kering', 'jumlah' => 20, 'satuan' => 'buah'],
                ['nama' => 'Lengkuas', 'jumlah' => 4, 'satuan' => 'ruas'],
            ],
            'langkah' => [
                'Potong daging gandik ukuran dadu besar, tiriskan sebentar.',
                'Haluskan cabai kering, kemiri, dan bumbu lainnya menjadi bumbu rendang.',
                'Masak santan bersama bumbu halus, serai, daun jeruk, dan daun kunyit sambil terus diaduk.',
                'Setelah mendidih, masukkan daging dan kecilkan api.',
                'Masak 2,5 sampai 3 jam tanpa berhenti mengaduk agar santan tidak pecah.',
                'Lanjutkan sampai santan menyusut menjadi dedak dan warna berubah cokelat gelap.',
                'Diamkan sejenak supaya bumbu meresap sempurna sebelum disajikan.',
            ],
        ],
        [
            'judul' => 'Rawon Daging Surabaya',
            'kategori' => KategoriResep::DagingSapi,
            'daerah' => 'Jawa Timur',
            'waktu' => 90,
            'porsi' => 4,
            'kesulitan' => TingkatKesulitan::Sedang,
            'gambar' => 'rawon.jpg',
            'deskripsi' => 'Aroma kuat kluwek tua dipadukan kaldu sapi gurih, tauge pendek, dan telur asin masir yang menyeimbangkan kuah hitamnya.',
            'energi' => 350, 'protein' => 24.0, 'lemak' => 21.3, 'karbo' => 14.6,
            'bahan' => [
                ['nama' => 'Daging sapi sandung lamur', 'jumlah' => 700, 'satuan' => 'gram'],
                ['nama' => 'Kluwek tua', 'jumlah' => 8, 'satuan' => 'biji'],
                ['nama' => 'Daun bawang', 'jumlah' => 3, 'satuan' => 'batang'],
                ['nama' => 'Tauge pendek', 'jumlah' => 200, 'satuan' => 'gram'],
                ['nama' => 'Telur asin', 'jumlah' => 4, 'satuan' => 'butir'],
                ['nama' => 'Lengkuas', 'jumlah' => 3, 'satuan' => 'ruas'],
                ['nama' => 'Serai', 'jumlah' => 2, 'satuan' => 'batang'],
                ['nama' => 'Air asam jawa', 'jumlah' => 2, 'satuan' => 'sdm'],
            ],
            'langkah' => [
                'Rendam kluwek dalam air hangat sampai empuk, lalu haluskan.',
                'Rebus daging bersama serai dan lengkuas sampai empuk, sisihkan kaldunya.',
                'Tumis bumbu halus termasuk kluwek sampai harum dan tidak mentah.',
                'Tuang tumisan ke dalam kaldu sapi, aduk rata sampai kuah berwarna hitam pekat.',
                'Masukkan daging yang telah dipotong, masak 15 menit agar bumbu meresap.',
                'Sajikan bersama tauge pendek, telur asin belah dua, dan sambal terasi.',
            ],
        ],
        [
            'judul' => 'Cireng Bumbu Rujak Salju',
            'kategori' => KategoriResep::Jajanan,
            'daerah' => 'Jawa Barat',
            'waktu' => 25,
            'porsi' => 3,
            'kesulitan' => TingkatKesulitan::Mudah,
            'gambar' => 'cireng-rujak.jpg',
            'deskripsi' => 'Tekstur tapioka renyah di luar kenyal lembut di dalam dengan cocolan asam manis pedas gula aren yang kental.',
            'energi' => 185, 'protein' => 3.0, 'lemak' => 6.8, 'karbo' => 29.2,
            'bahan' => [
                ['nama' => 'Tepung tapioka', 'jumlah' => 250, 'satuan' => 'gram'],
                ['nama' => 'Daun bawang', 'jumlah' => 3, 'satuan' => 'batang'],
                ['nama' => 'Bawang putih', 'jumlah' => 4, 'satuan' => 'siung'],
                ['nama' => 'Gula aren', 'jumlah' => 120, 'satuan' => 'gram'],
                ['nama' => 'Cabai rawit', 'jumlah' => 8, 'satuan' => 'buah'],
                ['nama' => 'Asam jawa', 'jumlah' => 1, 'satuan' => 'sdt'],
                ['nama' => 'Garam', 'jumlah' => 1, 'satuan' => 'sdt'],
            ],
            'langkah' => [
                'Seduh tepung tapioka dengan air panas sampai menggumpal, uleni sebentar.',
                'Tambahkan daun bawang dan bawang putih goreng, aduk rata.',
                'Bentuk pipih sesuai selera, sisihkan sebelum digoreng.',
                'Rebus gula aren, cabai, asam jawa, dan garam untuk bumbu rujak sampai kental.',
                'Goreng cireng dengan minyak panas sampai mengembang dan renyah.',
                'Sajikan hangat dengan bumbu rujak sebagai cocolan.',
            ],
        ],
    ];

    /**
     * Profil bahan, langkah, dan rentang gizi per kategori.
     *
     * Bahan dan langkah tetap disusun per kategori karena menulis 300 resep
     * secara manual tidak mungkin. Yang membuat hasilnya tidak terasa palsu
     * adalah deskripsinya — diambil dari ringkasan Wikipedia yang asli, bukan
     * kalimat buatan.
     *
     * @var array<string, array{
     *     utama: array{0: string, 1: int, 2: int},
     *     langkah: array<int, string>,
     *     waktu: array{0: int, 1: int},
     *     gizi: array<string, array{0: int, 1: int}>
     * }>
     */
    private const PROFIL = [
        KategoriResep::DagingSapi->value => [
            'utama' => ['Daging sapi', 400, 900],
            'waktu' => [45, 180],
            'langkah' => [
                'Potong {utama} ukuran dadu, tiriskan sebentar sebelum dimasak.',
                'Haluskan bawang merah, bawang putih, kemiri, dan cabai, lalu tumis sampai harum dan minyaknya keluar.',
                'Masukkan {utama}, aduk sampai seluruh permukaannya terbalut bumbu.',
                'Tuang santan dan air secukupnya, masak dengan api kecil sambil sesekali diaduk.',
                'Masukkan serai, daun jeruk, dan lengkuas, lanjutkan sampai kuah menyusut dan bumbu meresap.',
                'Cicipi dan koreksi rasa, sajikan hangat bersama nasi putih.',
            ],
            'gizi' => ['energi' => [300, 650], 'protein' => [20, 40], 'lemak' => [12, 35], 'karbo' => [4, 25]],
        ],
        KategoriResep::AyamUnggas->value => [
            'utama' => ['Ayam kampung', 500, 1200],
            'waktu' => [35, 120],
            'langkah' => [
                'Potong {utama} sesuai selera, cuci bersih, lalu lumuri air jeruk nipis dan diamkan 10 menit.',
                'Haluskan bawang merah, bawang putih, kunyit, jahe, dan cabai menjadi bumbu ungkep.',
                'Masak {utama} bersama bumbu halus, serai, dan daun jeruk dengan api sedang sampai empuk.',
                'Tiriskan, lalu olesi kecap manis jika ingin dibakar.',
                'Bakar di atas teflon atau arang sambil sesekali diolesi bumbu sampai berkulit kecokelatan.',
                'Sajikan bersama sambal dan lalapan segar.',
            ],
            'gizi' => ['energi' => [250, 520], 'protein' => [22, 38], 'lemak' => [10, 28], 'karbo' => [5, 25]],
        ],
        KategoriResep::IkanSeafood->value => [
            'utama' => ['Ikan segar', 300, 800],
            'waktu' => [20, 70],
            'langkah' => [
                'Bersihkan {utama}, buang sisik dan insang, cuci sampai tidak berlendir.',
                'Lumuri air jeruk nipis dan garam, diamkan 15 menit supaya bau amisnya hilang.',
                'Haluskan bawang merah, bawang putih, kunyit, dan cabai, lalu balurkan ke {utama}.',
                'Masak sampai bumbu meresap — dikukus, dibakar, atau digoreng sesuai selera.',
                'Masukkan tomat dan daun kemangi di menit-menit terakhir supaya warnanya tetap segar.',
                'Sajikan hangat dengan sambal dan jeruk limau.',
            ],
            'gizi' => ['energi' => [180, 420], 'protein' => [20, 36], 'lemak' => [6, 22], 'karbo' => [4, 20]],
        ],
        KategoriResep::SayurNabati->value => [
            'utama' => ['Sayur mayur', 250, 600],
            'waktu' => [15, 50],
            'langkah' => [
                'Pilih dan potong {utama} sesuai selera, lalu cuci bersih.',
                'Tumis bawang merah, bawang putih, dan cabai sampai harum.',
                'Tuang santan atau air, masukkan {utama}, dan masak dengan api kecil.',
                'Tambahkan garam, sedikit gula, dan gula merah secukupnya, lalu aduk rata.',
                'Masak sampai {utama} empuk tetapi warnanya masih cerah, jangan terlalu lama.',
                'Sajikan hangat sebagai pendamping nasi putih.',
            ],
            'gizi' => ['energi' => [120, 320], 'protein' => [5, 16], 'lemak' => [4, 18], 'karbo' => [10, 32]],
        ],
        KategoriResep::NasiKarbo->value => [
            'utama' => ['Beras', 300, 700],
            'waktu' => [20, 90],
            'langkah' => [
                'Cuci {utama} sampai airnya jernih, lalu tiriskan.',
                'Tumis bawang merah, bawang putih, dan rempah sampai harum.',
                'Masukkan {utama}, aduk rata supaya terbalut minyak dan bumbu.',
                'Tuang air dengan perbandingan yang tepat, masak sampai meresap dan matang.',
                'Diamkan sejenak dengan tutup tertutup supaya teksturnya pulen.',
                'Sajikan bersama lauk pendamping dan sambal.',
            ],
            'gizi' => ['energi' => [320, 680], 'protein' => [8, 24], 'lemak' => [6, 24], 'karbo' => [40, 90]],
        ],
        KategoriResep::Jajanan->value => [
            'utama' => ['Tepung terigu', 200, 500],
            'waktu' => [15, 60],
            'langkah' => [
                'Ayak {utama} bersama garam supaya adonan halus dan tidak bergerindil.',
                'Tambahkan air sedikit demi sedikit sambil diuleni sampai kalis.',
                'Diamkan adonan 20 menit supaya hasilnya lebih lembut.',
                'Bentuk sesuai selera, lalu istirahatkan sebentar sebelum dimasak.',
                'Goreng dengan minyak panas dan api sedang sampai keemasan dan matang merata.',
                'Sajikan hangat, cocok untuk teman minum teh sore.',
            ],
            'gizi' => ['energi' => [180, 420], 'protein' => [3, 12], 'lemak' => [6, 20], 'karbo' => [25, 60]],
        ],
    ];

    /**
     * Isi database dengan resep contoh.
     *
     * Dua lapis: lima hidangan kurasi yang bahan dan langkahnya ditulis tangan,
     * lalu ratusan hidangan hasil panen Wikimedia Commons yang semuanya sudah
     * pasti punya foto (karena panennya memang memilih hidangan yang fotonya
     * tersedia).
     */
    public function run(): void
    {
        $penulis = $this->penulis();

        foreach (self::KURASI as $i => $resep) {
            Resep::query()->updateOrCreate(
                ['slug' => Str::slug($resep['judul'])],
                [
                    'user_id' => $penulis[$i % count($penulis)]->id,
                    'judul' => $resep['judul'],
                    'deskripsi' => $resep['deskripsi'],
                    'gambar' => 'images/landing/'.$resep['gambar'],
                    'daerah' => $resep['daerah'],
                    'kategori' => $resep['kategori'],
                    'tingkat_kesulitan' => $resep['kesulitan'],
                    'status' => StatusResep::Terverifikasi,
                    'waktu_masak_menit' => $resep['waktu'],
                    'porsi' => $resep['porsi'],
                    'energi_kkal' => $resep['energi'],
                    'protein_g' => $resep['protein'],
                    'lemak_g' => $resep['lemak'],
                    'karbohidrat_g' => $resep['karbo'],
                    'bahan' => $resep['bahan'],
                    'langkah' => $resep['langkah'],
                ],
            );
        }

        $jumlah = $this->dariPanen();

        $this->command?->info('Resep di database: '.Resep::query()->count()." ({$jumlah} dari hasil panen).");
    }

    /**
     * Buat resep dari dataset hasil panen Wikimedia Commons.
     */
    private function dariPanen(): int
    {
        $berkas = database_path('seeders/data/hidangan.json');

        if (! is_file($berkas)) {
            $this->command?->warn(
                'Lewati resep panen — database/seeders/data/hidangan.json tidak ada. '.
                'Jalankan dulu: node scripts/panen-hidangan.mjs lalu node scripts/unduh-foto.mjs',
            );

            return 0;
        }

        $hidangan = json_decode((string) file_get_contents($berkas), true);

        if (! is_array($hidangan)) {
            $this->command?->error('hidangan.json tidak berisi JSON yang valid.');

            return 0;
        }

        $penulis = User::query()->where('role', UserRole::Pengguna)->pluck('id');

        if ($penulis->isEmpty()) {
            $this->command?->error('Belum ada Kreator — jalankan UserSeeder lebih dulu.');

            return 0;
        }

        // Kunci kalimat deskripsi yang sudah terpakai, supaya dua hidangan
        // tidak pernah berbagi rangka, sifat, dan pendamping yang sama persis.
        $dipakaiDeskripsi = [];

        foreach ($hidangan as $h) {
            $kategori = $h['kategori'] ?? KategoriResep::Jajanan->value;
            $profil = self::PROFIL[$kategori] ?? self::PROFIL[KategoriResep::Jajanan->value];
            [$utama, $min, $max] = $profil['utama'];
            $jumlahUtama = fake()->numberBetween($min, $max);

            // Dihitung lebih dulu karena daftar bahan ikut menentukan isi
            // deskripsi cadangan, dan karena kuncinya harus tercatat setelah
            // kalimatnya jadi — bukan sebelum.
            $bahan = $this->bahan($utama, $jumlahUtama);

            $deskripsiAsli = $h['deskripsi'] ?? null;
            $deskripsi = $deskripsiAsli ?? self::deskripsiCadangan(
                $h['judul'],
                $h['slug'],
                $kategori,
                $h['daerah'] ?? null,
                $bahan,
                $dipakaiDeskripsi,
            );

            if ($deskripsiAsli === null) {
                $dipakaiDeskripsi[] = self::kunciDeskripsi($h['judul'], $deskripsi);
            }

            // firstOrCreate, bukan updateOrCreate, supaya created_at yang sudah
            // tersebar acak tidak tertimpa saat seeding dijalankan ulang.
            Resep::query()->firstOrCreate(
                ['slug' => $h['slug']],
                [
                    'user_id' => $penulis->random(),
                    'judul' => $h['judul'],
                    'deskripsi' => $deskripsi,
                    'gambar' => 'images/resep/'.$h['slug'].'.jpg',
                    'daerah' => $h['daerah'] ?? null,
                    'kategori' => $kategori,
                    'tingkat_kesulitan' => fake()->randomElement(TingkatKesulitan::cases()),
                    'status' => $this->statusAcak(),
                    'waktu_masak_menit' => fake()->numberBetween($profil['waktu'][0], $profil['waktu'][1]),
                    'porsi' => fake()->numberBetween(2, 6),
                    'energi_kkal' => $this->rentang($profil['gizi']['energi']),
                    'protein_g' => $this->rentang($profil['gizi']['protein']),
                    'lemak_g' => $this->rentang($profil['gizi']['lemak']),
                    'karbohidrat_g' => $this->rentang($profil['gizi']['karbo']),
                    // Standar — resep panen cukup layak ditampilkan sebagai
                    // resep yang bisa direproduksi (badge "Standar" muncul).
                    'gizi_mode' => 'otomatis',
                    'gizi_cakupan' => 100,
                    'hasil_total' => fake()->numberBetween(500, 2000),
                    'satuan_hasil' => 'gram',
                    'alat' => ['Panci', 'Kompor'],
                    'bahan' => $bahan,
                    'langkah' => collect($profil['langkah'])
                        ->map(fn (string $langkah) => str_replace('{utama}', Str::lower($utama), $langkah))
                        ->all(),
                    'created_at' => now()->subDays(fake()->numberBetween(1, 900)),
                    'updated_at' => now(),
                ],
            );
        }

        return count($hidangan);
    }

    /**
     * Bahan berupa daftar jsonb: bahan utama, aromatik dasar, dan pelengkap.
     *
     * @return array<int, array{nama: string, jumlah: int|float, satuan: string}>
     */
    private function bahan(string $utama, int $jumlah): array
    {
        // Nama pokok disamarkan persis dengan entri `bahan_pangan` agar
        // hitungan gizi otomatis (dan cakupan 100%) akurat.
        $pokok = match ($utama) {
            'Daging sapi' => 'Daging sapi has dalam',
            'Ayam kampung' => 'Daging ayam dada',
            'Ikan segar' => 'Ikan kembung',
            'Sayur mayur' => 'Kangkung',
            'Beras' => 'Beras putih',
            'Tepung terigu' => 'Tepung terigu',
            default => $utama,
        };

        return [
            ['nama' => $pokok, 'jumlah' => $jumlah, 'satuan' => 'gram'],
            ['nama' => 'Bawang merah', 'jumlah' => fake()->numberBetween(6, 14), 'satuan' => 'siung'],
            ['nama' => 'Bawang putih', 'jumlah' => fake()->numberBetween(3, 8), 'satuan' => 'siung'],
            ['nama' => 'Cabe merah besar', 'jumlah' => fake()->numberBetween(4, 12), 'satuan' => 'gram'],
            ['nama' => 'Santan kental', 'jumlah' => fake()->numberBetween(200, 900), 'satuan' => 'ml'],
            ['nama' => 'Serai', 'jumlah' => fake()->numberBetween(1, 3), 'satuan' => 'gram'],
            ['nama' => 'Minyak goreng', 'jumlah' => fake()->numberBetween(2, 5), 'satuan' => 'sdm'],
            ['nama' => 'Garam dapur', 'jumlah' => fake()->randomFloat(1, 1, 3), 'satuan' => 'gram'],
        ];
    }

    /**
     * Status campuran supaya data tidak terlihat seperti hasil ekspor massal.
     */
    private function statusAcak(): StatusResep
    {
        $n = fake()->numberBetween(1, 100);

        return match (true) {
            $n <= 60 => StatusResep::Terverifikasi,
            $n <= 95 => StatusResep::Terbit,
            default => StatusResep::Draft,
        };
    }

    /**
     * Nilai gizi acak di dalam rentang yang lazim untuk kategori tersebut.
     */
    private function rentang(array $batas): float
    {
        return (float) fake()->randomFloat(1, $batas[0], $batas[1]);
    }

    /**
     * Cadangan deskripsi bila hidangan tidak punya artikel Wikipedia.
     *
     * Deterministik dari slug — bukan dari Faker. Dua alasannya: `db:seed`
     * yang dijalankan ulang selalu menghasilkan kalimat yang sama, dan
     * perbaikan massal terhadap resep yang sudah tersimpan bisa memanggil
     * fungsi ini persis seperti saat seeding, tanpa perlu mengulang seeding.
     *
     * Versi sebelumnya memakai SATU rangka kalimat dengan empat pilihan sifat
     * dan dua pendamping. Karena 98 dari 385 hidangan tidak punya artikel,
     * tiga sampai empat dari sebelas kartu di satu halaman bisa menampilkan
     * kalimat yang sama persis — kelihatan seperti templat, bukan tulisan.
     *
     * Dua perangkat kata dipisah menurut kategori. Jajanan pasar tidak boleh
     * berpasangan dengan sambal atau nasi putih, dan lauk tidak boleh
     * berpasangan dengan kopi tubruk; keacakan yang tidak memperhatikan
     * kategori menghasilkan "es buah bercita rasa asin gurih, cocok disajikan
     * bersama sambal terasi" — lebih kacau daripada kalimat templat yang lama.
     *
     * @param  array<int, array{nama: string, jumlah: int|float, satuan: string}>  $bahan
     * @param  array<int, string>  $dipakai  kunci kalimat yang sudah terpakai hidangan lain
     */
    public static function deskripsiCadangan(
        string $judul,
        string $slug,
        string $kategori,
        ?string $daerah,
        array $bahan,
        array $dipakai = [],
    ): string {
        $jajan = $kategori === KategoriResep::Jajanan->value;

        $sifat = $jajan ? [
            'manis dan lembut',
            'wangi dan tidak terlalu manis',
            'renyah di luar dan empuk di dalam',
            'legit dengan aroma santan',
            'ringan dan menyegarkan',
            'manis gurih seimbang',
            'lembut dan bertekstur kenyal',
            'gurih manis dengan aroma pandan',
        ] : [
            'gurih dan lembut',
            'harum dan seimbang',
            'pekat dan kaya rempah',
            'renyah di luar dan lembut di dalam',
            'sedikit pedas dan wangi',
            'manis dan gurih seimbang',
            'asin gurih dengan sentuhan pedas',
            'wangi dan gurih meresap',
        ];

        $pendamping = $jajan ? [
            'teh manis hangat',
            'kopi tubruk hangat',
            'teh tawar hangat',
            'kopi susu',
            'santan hangat',
            'gula aren cair',
            'bubur kacang hijau',
            'teman minum teh sore',
        ] : [
            'nasi putih hangat',
            'nasi putih dan sambal bawang',
            'lalapan segar',
            'tahu goreng',
            'sambal terasi',
            'kerupuk renyah',
            'sambal matah',
            'nasi uduk',
        ];

        // Anggota pertama daftar bahan selalu bahan pokok kategorinya (daging
        // sapi, ikan segar, tepung terigu, ...). Keduanya hanya dipakai pada
        // rangka lauk. Seluruh kategori jajanan memakai daftar bahan yang
        // sama persis, sehingga menyebutnya akan melahirkan "es buah dari
        // tepung terigu dan cabai merah" — jajanan jadi tidak menyebut bahan
        // sama sekali.
        $nama = collect($bahan)->pluck('nama')->filter()->values();
        $pokok = Str::lower($nama->first() ?? 'bahan utama');

        $aromatik = $nama->slice(1, 3)->filter()->values();
        $bumbu = Str::lower($aromatik->first() ?? 'bumbu dapur');

        $lauk = [
            'Memadukan {pokok} dan {bumbu}, {judul} dimasak sampai bumbunya meresap lalu disajikan bersama {pendamping}.',
            '{judul} mengolah {pokok} bersama {bumbu} sehingga terasa {sifat}; sajian rumahan ini paling nikmat disantap bersama {pendamping}.',
            'Perpaduan {pokok} dan {bumbu} pada {judul} menghasilkan cita rasa {sifat}, lengkap ketika disantap bersama {pendamping}.',
            'Cukup {pokok} dan {bumbu}, {judul} sudah menghadirkan cita rasa {sifat} — hidangan sederhana yang selalu cocok bersama {pendamping}.',
            'Diracik dari {pokok} dan {bumbu}, {judul} punya rasa {sifat} yang mudah disukai siapa saja, terutama bersama {pendamping}.',
            'Hidangan sederhana dari {pokok} dan {bumbu} ini menghasilkan {judul} dengan rasa {sifat}; cocok disandingkan dengan {pendamping}.',
            'Rasa {sifat} hadir dari perpaduan {pokok} dan {bumbu} pada {judul}, semakin lengkap ketika disantap bersama {pendamping}.',
            'Khas {daerah}, {judul} memadukan {pokok} dengan {bumbu} sampai menghasilkan rasa {sifat} yang lengkap bersama {pendamping}.',
        ];

        $jajanan = [
            '{judul} punya rasa {sifat} dengan aroma yang mudah dikenali; paling nikmat disantap hangat bersama {pendamping}.',
            'Cemilan rumahan {judul} terasa {sifat}; paling enak disantap saat sedang berkumpul bersama {pendamping}.',
            '{judul} termasuk jajanan {sifat} yang sederhana namun selalu menggoda, terutama bersama {pendamping}.',
            'Rasa {sifat} menjadi ciri {judul}; sajian ini paling lengkap ketika disantap bersama {pendamping}.',
            'Sederhana namun mengenyangkan, {judul} hadir dengan rasa {sifat} — cocok disajikan bersama {pendamping}.',
            '{judul} dikenal sebagai jajanan yang {sifat}; paling pas disantap bersama {pendamping}.',
            'Aroma hangat {judul} melengkapi rasanya yang {sifat}; sajian ini semakin nikmat bersama {pendamping}.',
            'Khas {daerah}, {judul} punya ciri {sifat} yang diwariskan turun-temurun; jajanan ini pas disantap bersama {pendamping}.',
        ];

        $adaDaerah = $daerah !== null && $daerah !== '';
        $kerangka = $jajan ? $jajanan : $lauk;

        if (! $adaDaerah) {
            // Anggota terakhir kedua daftar rangka selalu yang memakai {daerah}.
            array_pop($kerangka);
        }

        $ruang = count($kerangka) * count($sifat) * count($pendamping);

        // Seme menentukan titik mulai, bukan pilihan akhir. Ruangnya nyata
        // sempit — 7 rangka x 8 sifat x 8 pendamping = 448 kombinasi untuk 98
        // hidangan, yang berarti sekitar sepuluh pasang kalimat kembar bila
        // hanya mengandalkan seme. Karena itu ruang dijelajahi berurutan dari
        // titik mulai dan kombinasi yang teksnya sudah dipakai dilewati.
        $seme = hexdec(substr(md5($slug), 0, 8)) % $ruang;
        $cadangan = '';

        for ($n = 0; $n < $ruang; $n++) {
            $k = ($seme + $n) % $ruang;
            $iRangka = $k % count($kerangka);
            $sisa = intdiv($k, count($kerangka));
            $iSifat = $sisa % count($sifat);

            $teks = strtr($kerangka[$iRangka], [
                '{judul}' => $judul,
                '{daerah}' => (string) $daerah,
                '{sifat}' => $sifat[$iSifat],
                '{pendamping}' => $pendamping[intdiv($sisa, count($sifat))],
                '{pokok}' => $pokok,
                '{bumbu}' => $bumbu,
            ]);

            if ($cadangan === '') {
                $cadangan = $teks;
            }

            $kunci = self::kunciDeskripsi($judul, $teks);

            if (! in_array($kunci, $dipakai, true) && self::kataBerulang($judul, $teks) === []) {
                return $teks;
            }
        }

        return $cadangan;
    }

    /**
     * Kunci pembanding kalimat deskripsi: teksnya tanpa judul.
     *
     * Dua deskripsi dengan kunci sama dianggap kembar meski judulnya berbeda
     * — inilah yang membuat "Cucur punya rasa manis dan lembut, ..." dan
     * "Bollen punya rasa manis dan lembut, ..." tetap terdeteksi.
     */
    public static function kunciDeskripsi(string $judul, string $deskripsi): string
    {
        return trim((string) preg_replace('/\s+/', ' ', str_ireplace($judul, '', $deskripsi)));
    }

    /**
     * Kata berulang di dalam satu kalimat, setelah judul dan kata penghubung
     * disingkirkan.
     *
     * "teksturnya yang lembut dan rasa lembut" mengembalikan ['lembut'].
     * Pengulangan seperti ini timbul ketika rangka kalimat menyisipkan kata
     * sifat miliknya sendiri, atau ketika sifat dan pendamping memilih kata
     * yang sama — keduanya harus dihindari, bukan dimaafkan.
     *
     * @return array<int, string>
     */
    public static function kataBerulang(string $judul, string $deskripsi): array
    {
        return collect(preg_split('/\s+/', mb_strtolower(self::kunciDeskripsi($judul, $deskripsi))))
            ->map(fn (string $k) => (string) preg_replace('/[^\p{L}]/u', '', $k))
            ->filter()
            ->reject(fn (string $k) => in_array($k, self::KATA_SAMBUNG, true))
            ->groupBy(fn (string $k) => $k)
            ->filter(fn ($g) => $g->count() > 1)
            ->keys()
            ->all();
    }

    /**
     * Kata penghubung yang bebas berulang dan tidak ikut diperiksa.
     */
    private const KATA_SAMBUNG = [
        'dan', 'yang', 'atau', 'serta', 'dengan', 'untuk', 'dari', 'pada', 'ke', 'di',
        'ini', 'itu', 'supaya', 'karena', 'bila', 'saat', 'namun', 'sudah', 'tidak',
        'adalah', 'juga', 'hanya', 'pun', 'lah', 'kah', 'tapi', 'sebagai', 'sampai',
        'ketika', 'sementara', 'sekitar', 'setiap', 'demi', 'bagi', 'hingga', 'per',
    ];

    /**
     * Daftar penulis tetap untuk resep kurasi.
     *
     * Deterministik (firstOrCreate by email) supaya nama chef di landing page
     * konsisten antar run, bukan nama acak dari UserFactory.
     *
     * @return Collection<int, User>
     */
    private function penulis(): iterable
    {
        $daftar = [
            ['Rena Sumardi', 'rena@kulinara.test'],
            ['Bagus Prakoso', 'bagus@kulinara.test'],
            ['Ayu Lestari', 'ayu@kulinara.test'],
            ['Tio Wijaya', 'tio@kulinara.test'],
        ];

        return collect($daftar)->map(fn (array $chef) => User::query()->firstOrCreate(
            ['email' => $chef[1]],
            [
                'name' => $chef[0],
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => UserRole::Pengguna,
            ],
        ));
    }
}
