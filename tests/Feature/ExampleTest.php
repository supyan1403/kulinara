<?php

namespace Tests\Feature;

use App\Enums\KategoriResep;
use App\Models\Resep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Beranda harus tampil 200 walau database masih kosong.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->get('/')->assertStatus(200);
    }

    /**
     * Kartu di landing page diambil dari tabel resep, bukan data mati di JSX.
     */
    public function test_homepage_shows_published_photo_recipes(): void
    {
        $foto = ['status' => 'terbit', 'gambar' => 'images/landing/rendang.jpg'];

        Resep::factory()->create($foto);
        Resep::factory()->create($foto);

        // Dua resep berfoto -> keduanya mengisi kolam slideshow hero sekaligus
        // grid. Tumpang tindih ini disengaja; lihat komentar di routes/web.php.
        $this->get('/')
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Welcome')
                ->has('resep', 2)
                ->has('unggulan', 2)
                ->has('unggulan.0.judul')
                ->has('unggulan.1.judul')
                ->has('unggulan.0.penulis'));
    }

    /**
     * Resep tanpa foto TETAP tampil dengan placeholder — di grid maupun di
     * slideshow hero, yang menjadi cadangan terakhir etalase.
     *
     * Dulu resep tanpa foto dibuang dari query, sehingga 3 dari 6 chip kategori
     * (ayam_unggas, sayur_nabati, nasi_karbo) berujung ke halaman kosong.
     */
    public function test_homepage_shows_recipes_without_photo(): void
    {
        Resep::factory()->create(['gambar' => null]);

        $this->get('/')
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Welcome')
                ->has('unggulan', 1)
                ->where('unggulan.0.gambar', null)
                ->has('resep', 1));
    }

    /**
     * Chip kategori tetap mengembalikan isi walaupun resepnya berfoto-sebagian.
     */
    public function test_homepage_category_filter_includes_photoless_recipes(): void
    {
        Resep::factory()->create([
            'kategori' => KategoriResep::SayurNabati,
            'gambar' => null,
        ]);

        $this->get('/?kategori=sayur_nabati')
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->where('kategoriAktif', 'sayur_nabati')
                ->has('unggulan', 1)
                ->where('jumlahHasil', 1));
    }

    /**
     * Etalase hero tidak boleh kehilangan isinya hanya karena pencarian
     * menemukan resep tanpa foto: slideshow jatuh ke resep berfoto mana pun.
     */
    public function test_homepage_slideshow_falls_back_to_site_wide_photo_recipes(): void
    {
        // Judul DAN deskripsi dua resep pihak ketiga di bawah disertakan
        // secara eksplisit, bukan dibiarkan acak dari pabrik. Tanpa ini tes
        // jadi rawan goyah: `deskripsi()` di ResepFactory dibangun dari judul
        // masakan yang dipilih acak, dan daftar MASAKAN memuat "Ayam Rica-Rica
        // Manado" serta "Nasi Kuning Manado Cakalang" — undian acak bisa
        // membuat salah satunya ikut memuat kata kunci "manado", sehingga
        // pencarian menemukan lebih dari satu hasil.
        Resep::factory()->create([
            'judul' => 'Ayam Woku Manado',
            'slug' => 'ayam-woku-manado',
            'gambar' => null,
        ]);
        Resep::factory()->create([
            'judul' => 'Rendang Padang',
            'deskripsi' => 'Rendang Padang khas Sumatera Barat, dimasak lama sampai bumbu meresap. Cocok untuk 4 porsi.',
            'gambar' => 'images/landing/rendang.jpg',
        ]);
        Resep::factory()->create([
            'judul' => 'Rawon Ngawi',
            'deskripsi' => 'Rawon Ngawi khas Jawa Timur, kuah hitam keluak yang pekat. Cocok untuk 2 porsi.',
            'gambar' => 'images/landing/rawon.jpg',
        ]);

        $this->get('/?q=manado')
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->where('kataCari', 'manado')
                ->where('jumlahHasil', 1)
                ->has('unggulan', 2)
                ->has('unggulan.0.judul')
                ->has('unggulan.1.judul')
                ->has('resep', 1));
    }

    /**
     * Pool slideshow dibatasi lima kartu supaya hero tidak ikut mengirim
     * seluruh tabel resep ke browser.
     */
    public function test_homepage_slideshow_pool_is_capped_at_five(): void
    {
        Resep::factory()->count(8)->create([
            'gambar' => 'images/landing/rendang.jpg',
        ]);

        $this->get('/')
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->has('unggulan', 5)
                ->has('resep', 4));
    }

    /**
     * Kolom pencarian hero menyaring lewat FTS dan kata kuncinya dikembalikan
     * ke props supaya input tidak kosong setelah submit.
     */
    public function test_homepage_search_filters_recipes(): void
    {
        Resep::factory()->create(['judul' => 'Rendang Padang', 'slug' => 'rendang-padang']);
        Resep::factory()->create(['judul' => 'Klepon Gula Merah', 'slug' => 'klepon-gula-merah']);

        $this->get('/?q=rendang')
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Welcome')
                ->where('kataCari', 'rendang')
                ->where('jumlahHasil', 1)
                ->where('jumlahResep', 2));
    }

    /**
     * Pencarian tanpa hasil harus tetap 200 dengan hitungan nol.
     */
    public function test_homepage_search_with_no_results_is_not_an_error(): void
    {
        Resep::factory()->create();

        $this->get('/?q=zxcvbnm')
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->has('resep', 0)
                ->has('unggulan', 0)
                ->where('jumlahHasil', 0)
                ->where('jumlahResep', 1));
    }

    /**
     * Chip kategori menyaring lewat query string — nilai asing diabaikan.
     */
    public function test_homepage_ignores_unknown_category(): void
    {
        $this->get('/?kategori=tidak-ada')
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->where('kategoriAktif', null));
    }
}
