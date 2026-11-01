<?php

namespace Tests\Feature;

use App\Enums\StatusResep;
use App\Enums\TingkatKesulitan;
use App\Models\Bahan;
use App\Models\Resep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResepIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Direktori harus terbuka tanpa login sama sekali. Halaman ini memang
     * hanya menampilkan isi yang sudah terlihat di beranda — pagarnya adalah
     * status resep, bukan akun pengunjung.
     */
    public function test_direktori_terbuka_tanpa_login(): void
    {
        Resep::factory()->count(3)->create();

        $this->get('/resep')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Resep/Index')
                ->has('resep.data', 3)
                ->has('kategoriTersedia', 6)
                ->where('kategoriAktif', null)
                ->where('kataCari', '')
                // Lihat catatannya di ResepDetailTest: tanpa shared props ini,
                // footer halaman ini membuka grup "Akun" tanpa isi.
                ->where('canLogin', true)
                ->where('canRegister', true));
    }

    /**
     * Draft tetap tersembunyi dari direktori, persis seperti di beranda —
     * keduanya memakai scope yang sama lewat Resep::scopeDirektori().
     */
    public function test_draft_tidak_tampil_di_direktori(): void
    {
        Resep::factory()->count(2)->create(['status' => StatusResep::Terbit]);
        Resep::factory()->count(2)->create(['status' => StatusResep::Draft]);

        $this->get('/resep')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('resep.data', 2)->where('resep.total', 2));
    }

    /**
     * Dua belas resep per halaman, dengan paginator yang menyebut jumlah
     * seluruhnya — pengunjung tahu bahwa masih ada halaman berikutnya.
     */
    public function test_direktori_berpaginasi_dua_belas_per_halaman(): void
    {
        Resep::factory()->count(14)->create();

        $this->get('/resep')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('resep.data', 12)
                ->where('resep.total', 14)
                ->where('resep.current_page', 1)
                ->where('resep.last_page', 2));

        $this->get('/resep?page=2')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('resep.data', 2)
                ->where('resep.current_page', 2));
    }

    /**
     * Saringan ikut terbawa ke halaman berikutnya. Tanpa `withQueryString()`
     * di controller, mencari "rendang" lalu membuka halaman 2 akan
     * mengembalikan seluruh tabel seolah pencarian tidak pernah terjadi.
     */
    public function test_saringan_terbawa_ke_halaman_berikutnya(): void
    {
        Resep::factory()->count(13)->create(['judul' => 'Rendang Padang']);

        $this->get('/resep?q=rendang&page=2')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('kataCari', 'rendang')
                ->has('resep.data', 1)
                ->where('resep.total', 13)
                ->where('resep.current_page', 2)
                ->where(
                    'resep.links.1.url',
                    fn ($url) => is_string($url) && str_contains($url, 'q=rendang'),
                ));
    }

    /**
     * Nilai kategori di luar enum diabaikan, bukan mematikan halaman.
     */
    public function test_kategori_asing_diabaikan(): void
    {
        $this->get('/resep?kategori=tidak-ada')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('kategoriAktif', null));
    }

    /**
     * Pencarian di direktori menyaring isi yang tampil, bukan sekadar
     * menampilkan kata kunci kembali ke kolom input.
     */
    public function test_pencarian_menyaring_isi_direktori(): void
    {
        Resep::factory()->create(['judul' => 'Rendang Padang']);
        Resep::factory()->create(['judul' => 'Klepon Gula Merah']);

        $this->get('/resep?q=rendang')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('resep.data', 1)
                ->where('resep.data.0.judul', 'Rendang Padang'));
    }

    /**
     * Saringan tingkat kesulitan hanya menampilkan resep pada tingkat itu.
     */
    public function test_saringan_tingkat_kesulitan(): void
    {
        Resep::factory()->create(['tingkat_kesulitan' => TingkatKesulitan::Mudah, 'judul' => 'Resep Mudah']);
        Resep::factory()->create(['tingkat_kesulitan' => TingkatKesulitan::Sulit, 'judul' => 'Resep Sulit']);

        $this->get('/resep?kesulitan=mudah')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('kesulitan', 'mudah')
                ->has('resep.data', 1)
                ->where('resep.data.0.judul', 'Resep Mudah'));
    }

    /**
     * Saringan durasi maksimal membuang resep yang memasaknya lebih lama.
     */
    public function test_saringan_durasi_maksimal(): void
    {
        Resep::factory()->create(['waktu_masak_menit' => 15, 'judul' => 'Resep Cepat']);
        Resep::factory()->create(['waktu_masak_menit' => 120, 'judul' => 'Resep Lama']);

        $this->get('/resep?maks_menit=30')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('resep.data', 1)
                ->where('resep.data.0.judul', 'Resep Cepat'));
    }

    /**
     * Saringan rating minimum hanya menampilkan resep di atas ambang batas.
     */
    public function test_saringan_rating_minimum(): void
    {
        Resep::factory()->create(['rata_rating' => 4.8, 'judul' => 'Resep Bintang']);
        Resep::factory()->create(['rata_rating' => 2.5, 'judul' => 'Resep Biasa']);

        $this->get('/resep?min_rating=4')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('resep.data', 1)
                ->where('resep.data.0.judul', 'Resep Bintang'));
    }

    /**
     * Saringan bahan mencocokkan nama bahan di dalam kolom jsonb.
     */
    public function test_saringan_bahan(): void
    {
        Resep::factory()->create([
            'judul' => 'Resep Bersantan',
            'bahan' => [['nama' => 'Santan kental', 'jumlah' => 1, 'satuan' => 'santan']],
        ]);
        Resep::factory()->create([
            'judul' => 'Resep Tanpa Santan',
            'bahan' => [['nama' => 'Air', 'jumlah' => 500, 'satuan' => 'ml']],
        ]);

        $this->get('/resep?bahan=santan')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('resep.data', 1)
                ->where('resep.data.0.judul', 'Resep Bersantan'));
    }

    /**
     * Saringan `bahan_id` hanya menangkap resep yang barisnya benar-benar
     * dirujuk ke `bahan_pangan` — bukan sekadar namanya mirip.
     */
    public function test_saringan_bahan_id(): void
    {
        $santan = Bahan::factory()->create(['nama' => 'Santan kental']);
        $beras = Bahan::factory()->create(['nama' => 'Beras putih']);

        Resep::factory()->create([
            'judul' => 'Resep Bersantan',
            'bahan' => [['nama' => 'Santan kental', 'jumlah' => 400, 'satuan' => 'ml', 'bahan_id' => $santan->id]],
        ]);
        Resep::factory()->create([
            'judul' => 'Resep Nasi Uduk',
            'bahan' => [['nama' => 'Beras putih', 'jumlah' => 300, 'satuan' => 'gram', 'bahan_id' => $beras->id]],
        ]);
        // Baris yang namanya mengandung "santan" tapi TIDAK dirujuk —
        // tidak boleh ikut kena saat menyaring lewat bahan_id.
        Resep::factory()->create([
            'judul' => 'Resep Santan Palsu',
            'bahan' => [['nama' => 'Santan bubuk', 'jumlah' => 2, 'satuan' => 'sdt']],
        ]);

        $this->get('/resep?bahan_id='.$santan->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('resep.data', 1)
                ->where('resep.data.0.judul', 'Resep Bersantan'));

        $this->get('/resep?bahan_id='.$beras->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('resep.data', 1)
                ->where('resep.data.0.judul', 'Resep Nasi Uduk'));
    }
}
