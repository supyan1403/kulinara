<?php

namespace Database\Factories;

use App\Enums\KategoriResep;
use App\Enums\StatusResep;
use App\Enums\TingkatKesulitan;
use App\Models\Resep;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Resep>
 */
class ResepFactory extends Factory
{
    protected $model = Resep::class;

    /**
     * Daftar masakan Indonesia nyata — dipakai supaya data contoh tidak
     * berupa "Recipe 1" khas faker, melainkan hidangan yang masuk akal.
     *
     * Sengaja TIDAK memuat lima hidangan yang sudah dikurasi ResepSeeder
     * (Pepes Ikan Mas, Risoles, Rendang, Rawon, Cireng) agar slug tidak
     * bertabrakan.
     *
     * @var array<int, array{judul: string, kategori: KategoriResep, daerah: string, waktu: int, kesulitan: TingkatKesulitan}>
     */
    private const MASAKAN = [
        ['judul' => 'Semur Daging Betawi', 'kategori' => KategoriResep::DagingSapi, 'daerah' => 'DKI Jakarta', 'waktu' => 75, 'kesulitan' => TingkatKesulitan::Sedang],
        ['judul' => 'Tongseng Sapi Khas Solo', 'kategori' => KategoriResep::DagingSapi, 'daerah' => 'Jawa Tengah', 'waktu' => 60, 'kesulitan' => TingkatKesulitan::Sedang],
        ['judul' => 'Sate Maranggi Cianjur', 'kategori' => KategoriResep::DagingSapi, 'daerah' => 'Jawa Barat', 'waktu' => 50, 'kesulitan' => TingkatKesulitan::Mudah],
        ['judul' => 'Empal Gentong Cirebon', 'kategori' => KategoriResep::DagingSapi, 'daerah' => 'Jawa Barat', 'waktu' => 80, 'kesulitan' => TingkatKesulitan::Sedang],
        ['judul' => 'Gulai Cincang Padang', 'kategori' => KategoriResep::DagingSapi, 'daerah' => 'Sumatera Barat', 'waktu' => 90, 'kesulitan' => TingkatKesulitan::Sedang],
        ['judul' => 'Soto Betawi Kuah Susu', 'kategori' => KategoriResep::DagingSapi, 'daerah' => 'DKI Jakarta', 'waktu' => 70, 'kesulitan' => TingkatKesulitan::Sedang],
        ['judul' => 'Konro Bakar Makassar', 'kategori' => KategoriResep::DagingSapi, 'daerah' => 'Sulawesi Selatan', 'waktu' => 120, 'kesulitan' => TingkatKesulitan::Sulit],
        ['judul' => 'Ayam Bakar Taliwang', 'kategori' => KategoriResep::AyamUnggas, 'daerah' => 'Nusa Tenggara Barat', 'waktu' => 70, 'kesulitan' => TingkatKesulitan::Sedang],
        ['judul' => 'Ayam Woku Belanga', 'kategori' => KategoriResep::AyamUnggas, 'daerah' => 'Sulawesi Utara', 'waktu' => 55, 'kesulitan' => TingkatKesulitan::Sedang],
        ['judul' => 'Opor Ayam Kampung', 'kategori' => KategoriResep::AyamUnggas, 'daerah' => 'Jawa Tengah', 'waktu' => 65, 'kesulitan' => TingkatKesulitan::Mudah],
        ['judul' => 'Ayam Betutu Gilimanuk', 'kategori' => KategoriResep::AyamUnggas, 'daerah' => 'Bali', 'waktu' => 210, 'kesulitan' => TingkatKesulitan::Sulit],
        ['judul' => 'Ayam Goreng Lengkuas', 'kategori' => KategoriResep::AyamUnggas, 'daerah' => 'Jawa Barat', 'waktu' => 45, 'kesulitan' => TingkatKesulitan::Mudah],
        ['judul' => 'Bebek Madura Bumbu Hitam', 'kategori' => KategoriResep::AyamUnggas, 'daerah' => 'Jawa Timur', 'waktu' => 150, 'kesulitan' => TingkatKesulitan::Sulit],
        ['judul' => 'Ayam Tangkap Aceh', 'kategori' => KategoriResep::AyamUnggas, 'daerah' => 'Aceh', 'waktu' => 40, 'kesulitan' => TingkatKesulitan::Sedang],
        ['judul' => 'Ayam Rica-Rica Manado', 'kategori' => KategoriResep::AyamUnggas, 'daerah' => 'Sulawesi Utara', 'waktu' => 45, 'kesulitan' => TingkatKesulitan::Sedang],
        ['judul' => 'Ikan Bakar Jimbaran', 'kategori' => KategoriResep::IkanSeafood, 'daerah' => 'Bali', 'waktu' => 45, 'kesulitan' => TingkatKesulitan::Mudah],
        ['judul' => 'Udang Saus Padang', 'kategori' => KategoriResep::IkanSeafood, 'daerah' => 'Sumatera Barat', 'waktu' => 30, 'kesulitan' => TingkatKesulitan::Mudah],
        ['judul' => 'Cumi Saus Tiram Mentega', 'kategori' => KategoriResep::IkanSeafood, 'daerah' => 'DKI Jakarta', 'waktu' => 25, 'kesulitan' => TingkatKesulitan::Mudah],
        ['judul' => 'Ikan Kuah Kuning Maluku', 'kategori' => KategoriResep::IkanSeafood, 'daerah' => 'Maluku', 'waktu' => 40, 'kesulitan' => TingkatKesulitan::Sedang],
        ['judul' => 'Mangut Lele Asap Yogya', 'kategori' => KategoriResep::IkanSeafood, 'daerah' => 'DI Yogyakarta', 'waktu' => 50, 'kesulitan' => TingkatKesulitan::Sedang],
        ['judul' => 'Pempek Kapal Selam Palembang', 'kategori' => KategoriResep::IkanSeafood, 'daerah' => 'Sumatera Selatan', 'waktu' => 60, 'kesulitan' => TingkatKesulitan::Sedang],
        ['judul' => 'Sayur Lodeh Terong', 'kategori' => KategoriResep::SayurNabati, 'daerah' => 'Jawa Tengah', 'waktu' => 35, 'kesulitan' => TingkatKesulitan::Mudah],
        ['judul' => 'Gudeg Yu Djum', 'kategori' => KategoriResep::SayurNabati, 'daerah' => 'DI Yogyakarta', 'waktu' => 180, 'kesulitan' => TingkatKesulitan::Sulit],
        ['judul' => 'Plecing Kangkung Lombok', 'kategori' => KategoriResep::SayurNabati, 'daerah' => 'Nusa Tenggara Barat', 'waktu' => 20, 'kesulitan' => TingkatKesulitan::Mudah],
        ['judul' => 'Gado-Gado Siram Betawi', 'kategori' => KategoriResep::SayurNabati, 'daerah' => 'DKI Jakarta', 'waktu' => 45, 'kesulitan' => TingkatKesulitan::Mudah],
        ['judul' => 'Sayur Asam Sunda', 'kategori' => KategoriResep::SayurNabati, 'daerah' => 'Jawa Barat', 'waktu' => 30, 'kesulitan' => TingkatKesulitan::Mudah],
        ['judul' => 'Urap Sayur Bumbu Kelapa', 'kategori' => KategoriResep::SayurNabati, 'daerah' => 'Jawa Tengah', 'waktu' => 35, 'kesulitan' => TingkatKesulitan::Mudah],
        ['judul' => 'Tumis Bunga Pepaya Teri', 'kategori' => KategoriResep::SayurNabati, 'daerah' => 'Sulawesi Utara', 'waktu' => 25, 'kesulitan' => TingkatKesulitan::Mudah],
        ['judul' => 'Nasi Liwet Sunda', 'kategori' => KategoriResep::NasiKarbo, 'daerah' => 'Jawa Barat', 'waktu' => 40, 'kesulitan' => TingkatKesulitan::Mudah],
        ['judul' => 'Nasi Tumpeng Komplet', 'kategori' => KategoriResep::NasiKarbo, 'daerah' => 'Jawa', 'waktu' => 90, 'kesulitan' => TingkatKesulitan::Sedang],
        ['judul' => 'Bubur Ayam Cirebon', 'kategori' => KategoriResep::NasiKarbo, 'daerah' => 'Jawa Barat', 'waktu' => 60, 'kesulitan' => TingkatKesulitan::Mudah],
        ['judul' => 'Nasi Uduk Gurih Betawi', 'kategori' => KategoriResep::NasiKarbo, 'daerah' => 'DKI Jakarta', 'waktu' => 45, 'kesulitan' => TingkatKesulitan::Mudah],
        ['judul' => 'Nasi Kuning Manado Cakalang', 'kategori' => KategoriResep::NasiKarbo, 'daerah' => 'Sulawesi Utara', 'waktu' => 50, 'kesulitan' => TingkatKesulitan::Sedang],
        ['judul' => 'Lontong Sayur Medan', 'kategori' => KategoriResep::NasiKarbo, 'daerah' => 'Sumatera Utara', 'waktu' => 75, 'kesulitan' => TingkatKesulitan::Sedang],
        ['judul' => 'Klepon Gula Merah', 'kategori' => KategoriResep::Jajanan, 'daerah' => 'Jawa', 'waktu' => 40, 'kesulitan' => TingkatKesulitan::Mudah],
        ['judul' => 'Dadar Gulung Unti Kelapa', 'kategori' => KategoriResep::Jajanan, 'daerah' => 'Jawa', 'waktu' => 45, 'kesulitan' => TingkatKesulitan::Mudah],
        ['judul' => 'Bika Ambon Medan', 'kategori' => KategoriResep::Jajanan, 'daerah' => 'Sumatera Utara', 'waktu' => 120, 'kesulitan' => TingkatKesulitan::Sulit],
        ['judul' => 'Kue Lumpur Kentang', 'kategori' => KategoriResep::Jajanan, 'daerah' => 'Jawa Timur', 'waktu' => 40, 'kesulitan' => TingkatKesulitan::Mudah],
        ['judul' => 'Bakwan Jagung Renyah', 'kategori' => KategoriResep::Jajanan, 'daerah' => 'Jawa Timur', 'waktu' => 25, 'kesulitan' => TingkatKesulitan::Mudah],
        ['judul' => 'Serabi Kuah Kinca Pandan', 'kategori' => KategoriResep::Jajanan, 'daerah' => 'Jawa Barat', 'waktu' => 35, 'kesulitan' => TingkatKesulitan::Mudah],
    ];

    /**
     * Definisi bawaan.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $masakan = fake()->unique()->randomElement(self::MASAKAN);
        $porsi = fake()->numberBetween(2, 6);

        return [
            'user_id' => User::factory(),
            'judul' => $masakan['judul'],
            'slug' => $this->slugUnik($masakan['judul']),
            'deskripsi' => $this->deskripsi($masakan),
            'gambar' => null,
            'daerah' => $masakan['daerah'],
            'kategori' => $masakan['kategori'],
            'tingkat_kesulitan' => $masakan['kesulitan'],
            'status' => StatusResep::Terbit,
            'waktu_masak_menit' => $masakan['waktu'],
            'porsi' => $porsi,
            'energi_kkal' => fake()->numberBetween(120, 520),
            'protein_g' => fake()->randomFloat(1, 3, 32),
            'lemak_g' => fake()->randomFloat(1, 2, 24),
            'karbohidrat_g' => fake()->randomFloat(1, 8, 60),
            // Standar — dummy data cukup layak ditampilkan sebagai resep
            // yang bisa direproduksi (badge "Standar" muncul di kartu).
            'gizi_mode' => 'otomatis',
            'gizi_cakupan' => 100,
            'hasil_total' => fake()->numberBetween(500, 2000),
            'satuan_hasil' => 'gram',
            'alat' => ['Panci', 'Kompor', 'Wadah'],
            'bahan' => $this->bahan($masakan['kategori']),
            'langkah' => $this->langkah(),
        ];
    }

    /**
     * Slug yang dijamin tidak bertabrakan.
     *
     * `fake()->unique()` hanya mengingat judul yang dipilih lewat factory.
     * Ketika sebuah test menyetel `judul` secara eksplisit — seperti
     * 'Ayam Woku Belanga' — penghitung itu tidak tahu judul tersebut sudah
     * terpakai, dan factory masih bisa memilihnya lagi sehingga melanggar
     * UNIQUE constraint di kolom `slug`. Memeriksa database menutup celah itu
     * apa pun judul yang dipilih penguji.
     */
    private function slugUnik(string $judul): string
    {
        $dasar = Str::slug($judul);
        $slug = $dasar;
        $urutan = 1;

        while (Resep::query()->where('slug', $slug)->exists()) {
            $slug = $dasar.'-'.++$urutan;
        }

        return $slug;
    }

    /**
     * @param  array{judul: string, kategori: KategoriResep, daerah: string, waktu: int, kesulitan: TingkatKesulitan}  $masakan
     */
    private function deskripsi(array $masakan): string
    {
        return sprintf(
            '%s khas %s, dimasak %s menit dengan bumbu yang %s. Cocok untuk %d porsi.',
            $masakan['judul'],
            $masakan['daerah'],
            Str::lower($masakan['waktu']),
            fake()->randomElement(['meresap sempurna', 'harum dan seimbang', 'pekat dan kaya rempah', 'gurih lembut']),
            fake()->numberBetween(2, 6),
        );
    }

    /**
     * Bahan berupa daftar jsonb — tiap entri {nama, jumlah, satuan}.
     *
     * @return array<int, array{nama: string, jumlah: int|float, satuan: string}>
     */
    private function bahan(KategoriResep $kategori): array
    {
        $utama = match ($kategori) {
            KategoriResep::DagingSapi => 'Daging sapi has dalam',
            KategoriResep::AyamUnggas => 'Daging ayam dada',
            KategoriResep::IkanSeafood => 'Ikan kembung',
            KategoriResep::SayurNabati => 'Kangkung',
            KategoriResep::NasiKarbo => 'Beras putih',
            KategoriResep::Jajanan => 'Tepung terigu',
        };

        return [
            ['nama' => $utama, 'jumlah' => fake()->numberBetween(300, 800), 'satuan' => 'gram'],
            ['nama' => 'Bawang merah', 'jumlah' => fake()->numberBetween(6, 14), 'satuan' => 'siung'],
            ['nama' => 'Bawang putih', 'jumlah' => fake()->numberBetween(3, 8), 'satuan' => 'siung'],
            ['nama' => 'Cabe merah besar', 'jumlah' => fake()->numberBetween(4, 12), 'satuan' => 'gram'],
            ['nama' => 'Santan kental', 'jumlah' => fake()->numberBetween(200, 900), 'satuan' => 'ml'],
            ['nama' => 'Garam dapur', 'jumlah' => fake()->randomFloat(1, 1, 3), 'satuan' => 'gram'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function langkah(): array
    {
        return [
            'Siapkan semua bahan, cuci bersih, lalu potong sesuai selera.',
            'Tumis bumbu halus sampai harum dan minyaknya keluar.',
            'Masukkan bahan utama, aduk rata sampai terbalut bumbu.',
            'Tuang santan, masak dengan api kecil sambil sesekali diaduk.',
            'Cicipi dan koreksi rasa, masak sampai bumbu meresap sempurna.',
            'Sajikan hangat bersama nasi putih.',
        ];
    }

    /**
     * Resep yang belum terbit — tidak terlihat pengunjung.
     *
     * Keluarannya `Draft`, bukan `Diajukan`: keduanya sama-sama tersembunyi dari
     * direktori, dan Draft adalah keadaan yang paling sering dipakai pengujian
     * untuk memastikan sebuah halaman berujung 404.
     */
    public function draft(): static
    {
        return $this->state(fn () => ['status' => StatusResep::Draft]);
    }
}
