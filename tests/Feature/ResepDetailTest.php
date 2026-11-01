<?php

namespace Tests\Feature;

use App\Enums\KategoriResep;
use App\Enums\StatusResep;
use App\Models\Bahan;
use App\Models\Resep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResepDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Resep berstatus Terbit bisa dibuka lewat slug-nya, lengkap dengan label
     * kategori yang dipetakan di server supaya React tidak menyimpan daftar
     * label enum kedua.
     */
    public function test_published_recipe_detail_is_visible(): void
    {
        $resep = Resep::factory()->create([
            'judul' => 'Rendang Padang',
            'slug' => 'rendang-padang',
            'status' => StatusResep::Terbit,
            'kategori' => KategoriResep::DagingSapi,
        ]);

        $this->get('/resep/rendang-padang')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Resep/Detail')
                ->where('resep.judul', 'Rendang Padang')
                ->where('kategoriLabel', 'Daging Sapi')
                ->where('kesulitanLabel', $resep->tingkat_kesulitan->label())
                ->has('resep.penulis.name')
                ->has('resep.bahan')
                ->has('resep.langkah')
                // Dibagikan lewat HandleInertiaRequests::share(), bukan dari
                // closure beranda — tanpa ini footer membuka grup "Akun" kosong
                // dan tombol Masuk/Daftar hilang dari bilah navigasi.
                ->where('canLogin', true)
                ->where('canRegister', true));
    }

    /**
     * Resep Terverifikasi sama terbukanya dengan Terbit — keduanya adalah
     * status tayang, bukan status menunggu.
     */
    public function test_reviewed_recipe_detail_is_visible(): void
    {
        Resep::factory()->create([
            'judul' => 'Soto Banjar',
            'slug' => 'soto-banjar',
            'status' => StatusResep::Terverifikasi,
        ]);

        $this->get('/resep/soto-banjar')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('resep.judul', 'Soto Banjar'));
    }

    /**
     * Draft tidak pernah terlihat di luar Studio Dapur. Yang menjaganya adalah
     * scope `terbit()` — bukan satu pun cabang kondisi di controller.
     */
    public function test_draft_recipe_detail_is_not_found(): void
    {
        $resep = Resep::factory()->create(['status' => StatusResep::Draft]);

        $this->get("/resep/{$resep->slug}")->assertNotFound();
    }

    /**
     * Halaman detail mengirim ringkasan standarisasi — termasuk rendemen
     * bila resep memenuhi syarat "Standar".
     */
    public function test_detail_mengirim_ringkasan_standar(): void
    {
        Bahan::factory()->create([
            'nama' => 'Beras putih',
            'energi_kkal' => 360,
        ]);

        Resep::factory()->create([
            'judul' => 'Nasi Kuning',
            'slug' => 'nasi-kuning',
            'status' => StatusResep::Terbit,
            'gizi_mode' => 'otomatis',
            'gizi_cakupan' => 100,
            'hasil_total' => 900,
            'satuan_hasil' => 'gram',
            'bahan' => [
                ['nama' => 'Beras putih', 'jumlah' => 300, 'satuan' => 'gram'],
            ],
        ]);

        $this->get('/resep/nasi-kuning')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('standar.terpenuhi', true)
                // Float bulat diserialisasi sebagai integer oleh json_encode
                // (serialize_precision=-1 → representasi terpendek), jadi
                // diassert sebagai integer, bukan float.
                ->where('standar.hasilTotal', 900)
                ->where('standar.satuanHasil', 'gram')
                // 300 g beras → total gram bahan 300; rendemen 900/300 = 300%.
                ->where('standar.totalGramBahan', 300)
                ->where('standar.rendemen', 300)
                ->where('standar.giziCakupan', 100)
                ->where('standar.giziMode', 'otomatis'));
    }

    /**
     * Resep tanpa standarisasi (resep lama) tetap membuka halaman detail
     * dengan `standar.terpenuhi` false dan `rendemen` null — tidak ada
     * yang hilang, hanya badge yang tidak muncul.
     */
    public function test_detail_tanpa_standar_tetap_tampil(): void
    {
        Resep::factory()->create([
            'judul' => 'Sayur Asem',
            'slug' => 'sayur-asem',
            'status' => StatusResep::Terbit,
            'gizi_mode' => 'manual',
            'gizi_cakupan' => 0,
            'hasil_total' => null,
            'satuan_hasil' => null,
        ]);

        $this->get('/resep/sayur-asem')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('standar.terpenuhi', false)
                ->where('standar.rendemen', null)
                ->where('standar.hasilTotal', null)
                ->where('standar.satuanHasil', null)
                ->where('standar.giziMode', 'manual'));
    }

    /**
     * Slug yang tidak dikenal berujung 404, bukan halaman kosong yang tetap
     * memakai kerangka detail.
     */
    public function test_unknown_slug_is_not_found(): void
    {
        $this->get('/resep/tidak-pernah-ada')->assertNotFound();
    }

    /**
     * Blok "Resep lain" diisi empat kartu sekategori, tanpa menampilkan
     * resep yang sedang dibuka.
     */
    public function test_detail_menyajikan_resep_lain_sekategori(): void
    {
        $resep = Resep::factory()->create(['kategori' => KategoriResep::SayurNabati]);
        Resep::factory()->count(5)->create(['kategori' => KategoriResep::SayurNabati]);

        $this->get("/resep/{$resep->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('terkait', 4)
                ->where('terkait.0.kategori', KategoriResep::SayurNabati->value)
                // Resep yang sedang dibaca tidak boleh muncul di daftar kirinya.
                ->where('terkait', fn ($t) => collect($t)->pluck('slug')->doesntContain($resep->slug)));
    }

    /**
     * Bila kategorinya sendiri kurang dari empat, sisanya diisi dari kategori
     * lain — lebih berguna daripada memperlihatkan blok yang setengah kosong.
     */
    public function test_resep_lain_dilengkapi_dari_kategori_lain_bila_kurang(): void
    {
        $resep = Resep::factory()->create(['kategori' => KategoriResep::Jajanan]);
        Resep::factory()->count(1)->create(['kategori' => KategoriResep::Jajanan]);
        Resep::factory()->count(5)->create(['kategori' => KategoriResep::DagingSapi]);

        $this->get("/resep/{$resep->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('terkait', 4));
    }

    /**
     * Dengan satu-satunya resep di database, bloknya tidak dikirim sama sekali.
     * Halaman menyembunyikan bloknya, bukan merender grid tanpa isi.
     */
    public function test_blok_resep_lain_menghilang_bila_tidak_ada(): void
    {
        $resep = Resep::factory()->create();

        $this->get("/resep/{$resep->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('terkait', 0));
    }
}
