<?php

namespace Database\Seeders;

use App\Models\Resep;
use App\Models\Ulasan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Ulasan contoh untuk seluruh resep terbit.
 *
 * Tujuan seeder ini semata-mata agar aplikasi terlihat seperti sudah dipakai
 * orang: tanpa ulasan, setiap kartu resep kosong dan bagian ulasan di halaman
 * detail selalu berbunyi "Belum ada yang mengulas". Untuk keperluan demo itu
 * menyembunyikan fitur yang justru ingin ditunjukkan.
 *
 * Dijalankan TERPISAH, bukan lewat `DatabaseSeeder`:
 *
 *     php artisan db:seed --class=UlasanSeeder
 *
 * Alasannya seeder ini mengubah isi database secara besar-besaran, sementara
 * `db:seed` juga menjalankan UserSeeder dan ResepSeeder. Memisahkannya berarti
 * menjalankan ulang data pengguna tidak pernah menghapus ulasan yang sudah ada
 * (dan sebaliknya). Seeder ini juga idempoten — menjalankannya dua kali
 * menghasilkan keadaan yang sama, bukan dua kali lipat baris.
 *
 * PENTING — `DatabaseSeeder` memakai `WithoutModelEvents`, yang mematikan
 * observer. Karena itu `rata_rating` dan `jumlah_ulasan` di tabel resep TIDAK
 * terisi dengan sendirinya saat seeding, walaupun kelas ini memakai Eloquent.
 * Ringkasannya dihitung ulang di akhir lewat `rating:hitung-ulang`.
 */
class UlasanSeeder extends Seeder
{
    /**
     * Bagian resep yang sengaja dibiarkan tanpa ulasan.
     *
     * Resep yang baru terbit memang wajar belum dinilai siapa pun. Membiarkan
     * sebagian kosong membuat tampilan "Belum ada yang mengulas resep ini"
     * dan kartu tanpa bintang tetap teruji — kalau semua resep punya rating,
     * dua keadaan itu tidak pernah terlihat sampai ada pengguna sungguhan
     * membuat resep baru.
     */
    private const RASIO_TANPA_ULASAN = 0.10;

    /**
     * Persentase ulasan yang hanya memberi bintang tanpa komentar.
     */
    private const RASIO_TANPA_KOMENTAR = 0.30;

    /**
     * Persentase ulasan yang dibalas penulis resep.
     */
    private const RASIO_DIBALAS = 0.25;

    /**
     * Bobot rating — indeks 1..5.
     *
     * Sengaja tidak seragam. Kalau setiap bintang punya peluang sama, rata-rata
     * semua resep mengerucut ke 3.0 dan seluruh direktori terlihat seperti
     * hasil generate. Di dunia nyata orang jauh lebih sering memberi 4 atau 5
     * daripada 2 atau 1: yang kecewa biasanya tidak kembali menulis, yang puas
     * lah yang mengisi kolom ulasan.
     *
     * @var array<int, int>
     */
    private const BOBOT_RATING = [
        1 => 3,
        2 => 6,
        3 => 16,
        4 => 35,
        5 => 40,
    ];

    /**
     * Komentar per tingkat rating.
     *
     * Dipisah menurut rating supaya isinya masuk akal: ulasan dua bintang yang
     * berbunyi "enak sekali, terima kasih" langsung terlihat palsu dan
     * merusak kesan seluruh halaman. Keluhan ditulis untuk rating rendah,
     * pujian untuk rating tinggi, dan yang datar untuk rating tengah.
     *
     * @var array<int, array<int, string>>
     */
    private const KOMENTAR = [
        1 => [
            'Bumbunya terlalu asin untuk selera saya, tidak bisa dihabiskan.',
            'Langkahnya tidak jelas, hasilnya jadi beda dari yang saya harapkan.',
            'Bahan utamanya susah dicari, akhirnya saya ganti dan rasanya melenceng.',
            'Masaknya jauh lebih lama dari yang tertulis di resep.',
        ],
        2 => [
            'Hasilnya kurang sesuai, mungkin saya salah di bagian bumbunya.',
            'Teksnya agak membingungkan di langkah ketiga.',
            'Gurihnya kurang berasa, perlu ditambah bumbu lagi.',
            'Boleh ditambahi catatan porsi untuk anak-anak.',
        ],
        3 => [
            'Cukup enak, tapi saya perlu menyesuaikan bumbunya sedikit.',
            'Standar saja, tidak ada yang istimewa tapi juga tidak mengecewakan.',
            'Lumayan untuk masakan sehari-hari.',
            'Perlu waktu lebih lama dari perkiraan saya, hasilnya oke.',
        ],
        4 => [
            'Enak dan langkahnya mudah diikuti, semua di rumah suka.',
            'Bumbunya meresap sampai ke dalam, saya cuma kurangi cabainya.',
            'Sudah dua kali masak ini dan hasilnya konsisten.',
            'Praktis untuk makan malam, bahan-bahannya mudah dicari.',
            'Saya tambah sedikit gula dan hasilnya pas untuk keluarga.',
        ],
        5 => [
            'Luar biasa, ini yang paling enak yang pernah saya coba.',
            'Resep andalan sekarang. Terima kasih sudah dibagikan.',
            'Sempurna, tidak perlu diubah apa-apa.',
            'Semua bilang enak, langsung habis tidak sampai sisa.',
            'Bumbunya pas, aromanya sudah bikin lapar sebelum matang.',
            'Suami saya minta dimasak lagi minggu depan.',
            'Ikut resep ini dan langsung jadi, padahal saya masih pemula.',
        ],
    ];

    /**
     * Balasan yang lazim diucapkan penulis resep.
     *
     * @var array<int, string>
     */
    private const BALASAN = [
        'Terima kasih sudah mencoba dan meninggalkan ulasan!',
        'Senang mendengarnya, semoga cocok terus di rumah.',
        'Terima kasih masukannya, nanti saya perbaiki catatan resepnya.',
        'Betul, cabai bisa disesuaikan. Makasih sudah mampir!',
        'Wah terima kasih, semoga jadi masakan andalan juga di rumah.',
        'Terima kasih sudah mengoreksi, saya cek bagian langkahnya.',
    ];

    /**
     * Isi tabel ulasan dari nol, lalu hitung ulang seluruh ringkasan rating.
     */
    public function run(): void
    {
        // Dihapus dulu supaya idempoten. Lewat query builder, bukan model —
        // penghapusan massal memang tidak memicu observer, tapi tidak masalah
        // di sini: setiap ringkasan dihitung ulang di akhir, jadi tidak ada
        // nilai basi yang tertinggal.
        Ulasan::query()->delete();

        $pengguna = User::query()->get(['id', 'name']);

        if ($pengguna->isEmpty()) {
            $this->command?->warn('Tidak ada pengguna. Jalankan UserSeeder dulu.');

            return;
        }

        $resepList = Resep::query()->terbit()->get(['id', 'user_id', 'created_at', 'judul']);

        if ($resepList->isEmpty()) {
            $this->command?->warn('Tidak ada resep terbit. Jalankan ResepSeeder dulu.');

            return;
        }

        $dibiarkanKosong = (int) round($resepList->count() * self::RASIO_TANPA_ULASAN);

        // Resep yang sengaja dibiarkan kosong dipilih di muka, bukan diundi
        // saat perulangan. Dengan undian, hasilnya bisa jauh dari 10% — di
        // database kecil bahkan bisa nol, dan keadaan "belum ada ulasan" tidak
        // pernah terlihat. Memilih di muka membuat jumlahnya pasti.
        $idKosong = $resepList
            ->shuffle()
            ->take($dibiarkanKosong)
            ->pluck('id')
            ->all();

        $baris = [];

        foreach ($resepList as $resep) {
            if (in_array($resep->id, $idKosong, true)) {
                continue;
            }

            foreach ($this->ulasanUntuk($resep, $pengguna) as $ulasan) {
                $baris[] = $ulasan;
            }
        }

        // insert() massal, bukan create() satu per satu: dengan ribuan baris,
        // perbedaan waktunya hitungan menit versus detik. Observer tetap tidak
        // jalan — dan itu sudah diperhitungkan, ringkasan dihitung di akhir.
        foreach (array_chunk($baris, 500) as $potongan) {
            Ulasan::query()->insert($potongan);
        }

        $this->panggilHitungUlang();

        $this->command?->info(sprintf(
            'Ulasan dibuat: %s baris untuk %d resep.',
            number_format(count($baris), 0, ',', '.'),
            $resepList->count() - count($idKosong),
        ));

        $this->command?->info(sprintf(
            '%d resep sengaja dibiarkan tanpa ulasan.',
            count($idKosong),
        ));
    }

    /**
     * Susun baris ulasan untuk satu resep.
     *
     * @param  Collection<int, User>  $pengguna
     * @return array<int, array<string, mixed>>
     */
    private function ulasanUntuk(Resep $resep, Collection $pengguna): array
    {
        // Penulis resep TIDAK boleh mengulas karyanya sendiri — aturan yang
        // sama dengan yang dijaga UlasanPolicy. Kalau dilanggar di sini,
        // beberapa resep akan punya ulasan yang tidak mungkin ada lewat
        // antarmuka, dan itu baru ketahuan saat demo.
        $kandidat = $pengguna->where('id', '!=', $resep->user_id)->values();

        if ($kandidat->count() < 2) {
            return [];
        }

        $jumlah = fake()->numberBetween(2, 8);

        // Diambil dari kolam, bukan diundi satu per satu: dengan undian,
        // pengguna yang sama bisa terpilih dua kali dan melanggar unique
        // index (resep_id, user_id) saat insert massal.
        $pengulas = $kandidat->shuffle()->take($jumlah);

        if ($pengulas->isEmpty()) {
            return [];
        }

        $baris = [];
        // Ulasan tidak boleh lebih tua daripada resepnya — itu mustahil dan
        // ketahuan begitu tanggalnya diperiksa.
        $sejak = $resep->created_at ?? now()->subDays(30);
        $rentangHari = max(1, (int) $sejak->diffInDays(now()));

        foreach ($pengulas as $pengulasItem) {
            $rating = $this->rating();
            $dibalas = fake()->boolean((int) (self::RASIO_DIBALAS * 100));
            $waktu = $sejak->copy()->addDays(fake()->numberBetween(0, $rentangHari));

            $baris[] = [
                'resep_id' => $resep->id,
                'user_id' => $pengulasItem->id,
                'rating' => $rating,
                'komentar' => fake()->boolean((int) ((1 - self::RASIO_TANPA_KOMENTAR) * 100))
                    ? fake()->randomElement(self::KOMENTAR[$rating])
                    : null,
                'balasan' => $dibalas ? fake()->randomElement(self::BALASAN) : null,
                'balasan_at' => $dibalas ? $waktu->copy()->addDays(fake()->numberBetween(1, 5)) : null,
                'created_at' => $waktu,
                'updated_at' => $waktu,
            ];
        }

        return $baris;
    }

    /**
     * Undi rating sesuai bobot di BOBOT_RATING.
     *
     * `fake()->randomElement()` dengan daftar yang sudah diperbanyak: 3 salinan
     * angka 1, 6 salinan angka 2, dan seterusnya. Cara ini menghindari
     * penulisan ulang logika undian berbobot hanya untuk satu keperluan.
     */
    private function rating(): int
    {
        static $kolam = null;

        if ($kolam === null) {
            $kolam = [];

            foreach (self::BOBOT_RATING as $bintang => $bobot) {
                $kolam = array_merge($kolam, array_fill(0, $bobot, $bintang));
            }
        }

        return fake()->randomElement($kolam);
    }

    /**
     * Hitung ulang `rata_rating` dan `jumlah_ulasan` di seluruh resep.
     *
     * Diperlukan karena observer dimatikan selama seeding, jadi ringkasan
     * tidak ikut terisi saat baris ulasan dimasukkan. Memanggil command yang
     * sama dengan yang dipakai operator menjaga keduanya tidak menyimpang.
     */
    private function panggilHitungUlang(): void
    {
        $this->command?->call('rating:hitung-ulang');
    }
}
