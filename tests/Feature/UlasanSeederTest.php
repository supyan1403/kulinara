<?php

namespace Tests\Feature;

use App\Models\Resep;
use App\Models\Ulasan;
use App\Models\User;
use Database\Seeders\UlasanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Seeder ulasan contoh.
 *
 * Seeder ini menyentuh data dalam jumlah besar, jadi kesalahannya tidak
 * kelihatan satu per satu — baru ketahuan setelah seluruh database terisi dan
 * angka di layar terasa aneh. Pengujian di sini memeriksa hal-hal yang hanya
 * bisa diperiksa setelah semua baris masuk: apakah ringkasannya cocok, apakah
 * ada aturan yang dilanggar, dan apakah menjalankannya dua kali aman.
 */
class UlasanSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Menyiapkan sejumlah resep dan pengguna untuk diseed.
     *
     * Jumlah resep dibatasi di bawah 16 — `ResepFactory` memilih judul dari
     * kolam 16 masakan lewat `fake()->unique()`, jadi meminta lebih dari itu
     * dalam satu test akan menghabiskan kolamnya dan gagal dengan
     * "Maximum retries reached". Batas ini milik factory, bukan seeder.
     */
    private function siapkan(int $resep = 14, int $pengguna = 15): void
    {
        User::factory()->count($pengguna)->create();
        Resep::factory()->count($resep)->create();
    }

    public function test_seeder_mengisi_ulasan_untuk_sebagian_besar_resep(): void
    {
        $this->siapkan();

        $this->seed(UlasanSeeder::class);

        $berulasan = Resep::query()->terbit()->where('jumlah_ulasan', '>', 0)->count();

        $this->assertGreaterThan(0, $berulasan, 'Tidak ada satu pun resep yang punya ulasan.');
    }

    public function test_sebagian_resep_dibiarkan_tanpa_ulasan(): void
    {
        // Dengan 20 resep dan rasio 10%, dua di antaranya harusnya kosong.
        $this->siapkan(resep: 14);

        $this->seed(UlasanSeeder::class);

        $kosong = Resep::query()->terbit()->where('jumlah_ulasan', 0)->count();

        $this->assertGreaterThan(
            0,
            $kosong,
            'Semua resep terisi ulasan. Tampilan "belum ada ulasan" jadi tidak pernah teruji.',
        );
    }

    public function test_ringkasan_rating_cocok_dengan_jumlah_baris_sebenarnya(): void
    {
        $this->siapkan();

        $this->seed(UlasanSeeder::class);

        // Inilah pemeriksaan terpenting di berkas ini. Seeder mematikan
        // observer, jadi kalau `rating:hitung-ulang` lupa dipanggil, kolom
        // ringkasan akan tetap nol untuk semua resep — dan tidak ada galat
        // yang muncul saat seeding.
        foreach (Resep::query()->get() as $resep) {
            $sebenarnya = Ulasan::query()->where('resep_id', $resep->id)->count();

            $this->assertSame(
                (int) $resep->jumlah_ulasan,
                $sebenarnya,
                "Resep #{$resep->id} mencatat {$resep->jumlah_ulasan} ulasan, sebenarnya {$sebenarnya}.",
            );
        }
    }

    public function test_tidak_ada_ulasan_atas_resep_sendiri(): void
    {
        $this->siapkan();

        $this->seed(UlasanSeeder::class);

        $sendiri = Ulasan::query()
            ->join('resep', 'resep.id', '=', 'ulasan.resep_id')
            ->whereColumn('resep.user_id', 'ulasan.user_id')
            ->count();

        $this->assertSame(0, $sendiri, 'Ada penulis resep yang mengulas karyanya sendiri.');
    }

    public function test_tidak_ada_pengguna_yang_mengulas_resep_sama_dua_kali(): void
    {
        $this->siapkan();

        $this->seed(UlasanSeeder::class);

        $dobel = Ulasan::query()
            ->selectRaw('resep_id, user_id, count(*) as j')
            ->groupBy('resep_id', 'user_id')
            ->havingRaw('count(*) > 1')
            ->count();

        $this->assertSame(0, $dobel, 'Ada pengguna yang mengulas resep yang sama lebih dari sekali.');
    }

    public function test_ulasan_tidak_pernah_lebih_tua_daripada_resepnya(): void
    {
        $this->siapkan();

        $this->seed(UlasanSeeder::class);

        $terlaluTua = Ulasan::query()
            ->join('resep', 'resep.id', '=', 'ulasan.resep_id')
            ->whereColumn('ulasan.created_at', '<', 'resep.created_at')
            ->count();

        $this->assertSame(
            0,
            $terlaluTua,
            'Ada ulasan bertanggal sebelum resepnya dibuat — mustahil dan kelihatan palsu.',
        );
    }

    public function test_sebagian_ulasan_hanya_berisi_bintang(): void
    {
        $this->siapkan(resep: 15, pengguna: 25);

        $this->seed(UlasanSeeder::class);

        $tanpaKomentar = Ulasan::query()->whereNull('komentar')->count();

        $this->assertGreaterThan(0, $tanpaKomentar, 'Tidak ada ulasan tanpa komentar.');
    }

    public function test_sebagian_ulasan_dibalas_penulis_resep(): void
    {
        $this->siapkan(resep: 15, pengguna: 25);

        $this->seed(UlasanSeeder::class);

        $dibalas = Ulasan::query()->whereNotNull('balasan')->count();

        $this->assertGreaterThan(0, $dibalas, 'Tidak ada ulasan yang dibalas.');
    }

    public function test_rating_tertinggi_lebih_sering_daripada_terendah(): void
    {
        $this->siapkan(resep: 15, pengguna: 30);

        $this->seed(UlasanSeeder::class);

        $lima = Ulasan::query()->where('rating', 5)->count();
        $satu = Ulasan::query()->where('rating', 1)->count();

        // Bobotnya 40 berbanding 3. Kalau undiannya seragam, angka ini akan
        // berimbang dan seluruh direktori terlihat seperti hasil generate.
        $this->assertGreaterThan(
            $satu,
            $lima,
            'Rating 1 dan 5 muncul sama sering — bobotnya tidak bekerja.',
        );
    }

    public function test_menjalankan_dua_kali_tidak_menumpuk_baris(): void
    {
        $this->siapkan();

        $this->seed(UlasanSeeder::class);
        $pertama = Ulasan::query()->count();

        $this->seed(UlasanSeeder::class);
        $kedua = Ulasan::query()->count();

        // Jumlahnya boleh berbeda sedikit karena jumlah ulasan per resep
        // memang diundi — tapi harus di rentang yang sama, bukan berlipat.
        $this->assertLessThan(
            $pertama * 1.5,
            $kedua,
            'Menjalankan seeder dua kali menumpuk baris, bukan mengisinya ulang.',
        );

        foreach (Resep::query()->get() as $resep) {
            $this->assertSame(
                (int) $resep->jumlah_ulasan,
                Ulasan::query()->where('resep_id', $resep->id)->count(),
            );
        }
    }

    public function test_aman_dijalankan_saat_hanya_ada_satu_pengguna(): void
    {
        // Satu pengguna yang menulis semua resep. Tidak ada orang lain untuk
        // mengulas, dan mengulas karya sendiri tidak diizinkan — jadi tidak
        // ada satu pun ulasan yang bisa sah dibuat.
        $penulis = User::factory()->create();
        Resep::factory()->count(5)->create(['user_id' => $penulis->id]);

        $this->seed(UlasanSeeder::class);

        $this->assertSame(0, Ulasan::query()->count());
    }

    public function test_aman_dijalankan_saat_belum_ada_resep(): void
    {
        User::factory()->count(5)->create();

        $this->seed(UlasanSeeder::class);

        $this->assertSame(0, Ulasan::query()->count());
    }

    public function test_resep_dengan_penulis_satu_satunya_tidak_mendapat_ulasan(): void
    {
        // Hanya ada satu pengguna, dan ia penulis resepnya. Tidak ada orang
        // lain yang bisa mengulas, dan mengulas sendiri dilarang.
        $penulis = User::factory()->create();
        Resep::factory()->create(['user_id' => $penulis->id]);

        $this->seed(UlasanSeeder::class);

        $this->assertSame(0, Ulasan::query()->count());
    }
}
