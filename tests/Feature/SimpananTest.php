<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Resep;
use App\Models\Simpanan;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fitur "Simpan resep".
 *
 * Fitur ini adalah satu-satunya alasan konkret bagi seorang PEMBACA untuk
 * mendaftar: seluruh isi resep terbuka tanpa login, dan yang dijanjikan
 * login di sini bukanlah konten melainkan kelangsungan — tanda yang tersimpan
 * di akun, bisa dibuka dari perangkat mana pun.
 */
class SimpananTest extends TestCase
{
    use RefreshDatabase;

    /* ─────────────────────────── Tamu ─────────────────────────── */

    public function test_tamu_diarahkan_ke_halaman_masuk_ketika_menyimpan(): void
    {
        $resep = Resep::factory()->create();

        $this->post(route('simpanan.toggle', $resep->id))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('simpanan', 0);
    }

    public function test_tamu_diarahkan_ke_halaman_masuk_ketika_membuka_daftar(): void
    {
        $this->get(route('simpanan.index'))->assertRedirect(route('login'));
    }

    /* ─────────────────────────── Menyimpan ─────────────────────────── */

    public function test_menyimpan_resep_membuat_satu_baris(): void
    {
        $pengguna = User::factory()->create();
        $resep = Resep::factory()->create();

        $this->actingAs($pengguna)
            ->post(route('simpanan.toggle', $resep->id))
            ->assertRedirect();

        $this->assertDatabaseHas('simpanan', [
            'user_id' => $pengguna->id,
            'resep_id' => $resep->id,
        ]);
    }

    public function test_toggle_kedua_kali_menghapus_simpanan(): void
    {
        $pengguna = User::factory()->create();
        $resep = Resep::factory()->create();

        $this->actingAs($pengguna)->post(route('simpanan.toggle', $resep->id));
        $this->actingAs($pengguna)->post(route('simpanan.toggle', $resep->id));

        $this->assertDatabaseCount('simpanan', 0);
    }

    public function test_dua_orang_bisa_menyimpan_resep_yang_sama(): void
    {
        $resep = Resep::factory()->create();
        $saya = User::factory()->create();
        $kamu = User::factory()->create();

        $this->actingAs($saya)->post(route('simpanan.toggle', $resep->id));
        $this->actingAs($kamu)->post(route('simpanan.toggle', $resep->id));

        $this->assertDatabaseCount('simpanan', 2);
    }

    /* ─────────────────────────── Pagar ─────────────────────────── */

    public function test_resep_draft_tidak_bisa_disimpan(): void
    {
        $pengguna = User::factory()->create();
        $draft = Resep::factory()->create(['status' => 'draft']);

        $this->actingAs($pengguna)
            ->post(route('simpanan.toggle', $draft->id))
            ->assertStatus(404);

        $this->assertDatabaseCount('simpanan', 0);
    }

    /**
     * Alasannya bukan sekadar konsistensi: resep Draft tidak punya rute
     * publik, jadi menyimpannya akan membuat seseorang menimbun tautan yang
     * bahkan tidak bisa ia buka kembali.
     */
    public function test_simpanan_hanya_menampilkan_milik_sendiri(): void
    {
        $saya = User::factory()->create();
        $kamu = User::factory()->create();

        $resepSaya = Resep::factory()->create(['judul' => 'Resep Milik Saya']);
        $resepKamu = Resep::factory()->create(['judul' => 'Resep Milik Kamu']);

        $this->actingAs($saya)->post(route('simpanan.toggle', $resepSaya->id));
        $this->actingAs($kamu)->post(route('simpanan.toggle', $resepKamu->id));

        $this->actingAs($saya)
            ->get(route('simpanan.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Simpanan/Index')
                ->has('resep', 1)
                ->where('resep.0.judul', 'Resep Milik Saya'));
    }

    /* ─────────────────────────── Halaman detail ─────────────────────────── */

    public function test_halaman_detail_melaporkan_resep_sudah_disimpan(): void
    {
        $pengguna = User::factory()->create();
        $resep = Resep::factory()->create();

        $this->actingAs($pengguna)->post(route('simpanan.toggle', $resep->id));

        $this->actingAs($pengguna)
            ->get(route('resep.show', $resep->slug))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Resep/Detail')
                ->where('disimpan', true));
    }

    public function test_halaman_detail_tanpa_simpanan_melaporkan_false(): void
    {
        $pengguna = User::factory()->create();
        $resep = Resep::factory()->create();

        $this->actingAs($pengguna)
            ->get(route('resep.show', $resep->slug))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->where('disimpan', false));
    }

    /**
     * Tombol Simpan tampil untuk tamu juga — menyembunyikannya justru
     * menghapus satu-satunya alasan baginya untuk mendaftar. Yang berbeda
     * hanya perlakuannya: ia mengarah ke halaman masuk, bukan menyimpan.
     */
    public function test_halaman_detail_tamu_tetap_melaporkan_false(): void
    {
        $resep = Resep::factory()->create();

        $this->get(route('resep.show', $resep->slug))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->where('disimpan', false));
    }

    /* ─────────────────── Penurunan status ─────────────────── */

    /**
     * Simpanan yang resepnya turun ke Draft disembunyikan otomatis —
     * halaman detail memakai scope `terbit()`, jadi kartu di daftar pasti
     * berujung 404. Menampilkannya berarti menjanjikan tautan mati.
     */
    public function test_resep_yang_turun_ke_draft_hilang_dari_daftar_simpanan(): void
    {
        $pembaca = User::factory()->create();
        $resep = Resep::factory()->create(['status' => 'terbit']);

        $this->actingAs($pembaca)->post(route('simpanan.toggle', $resep->id));

        // Penulis menurunkan resep ke draft setelah dibaca simpan.
        $resep->update(['status' => 'draft']);

        $this->actingAs($pembaca)
            ->get(route('simpanan.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Simpanan/Index')
                ->has('resep', 0));
    }

    public function test_halaman_dashboard_menampilkan_hitungan_simpanan_yang_bener(): void
    {
        // /dashboard sekarang menampilkan milik pengguna — bukan katalog.
        // Test ini menjaga agar:
        // - prop jumlahTersimpan sama dengan isi grid tersimpan
        // - draft yang pernah disimpan (atau masih ada barisnya) TIDAK muncul
        //   di tersimpan (scope terbit() memfilter), jadi kartu tidak bisa
        //   mengarah ke halaman 404
        // - prop terbaru sudah tidak ada, supaya dashboard tidak jadi salinan
        //   /resep
        $pembaca = User::factory()->create();
        $tayang = Resep::factory()->create(['status' => 'terbit']);
        $draft = Resep::factory()->create(['status' => 'draft']);

        $this->actingAs($pembaca)->post(route('simpanan.toggle', $tayang->id));

        // Baris simpanan untuk draft dibuat langsung — toggle menolaknya,
        // tapi sisa data lama bisa mengandungnya.
        Simpanan::create([
            'user_id' => $pembaca->id,
            'resep_id' => $draft->id,
        ]);

        $this->actingAs($pembaca)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->where('jumlahTersimpan', 1)
                ->where('tersimpan', fn ($items) => collect($items)->count() === 1
                    && collect($items)->first()['slug'] === $tayang->slug)
            );
    }

    public function test_pengguna_baru_mendapat_dashboard_kosong_yang_tetap_berguna(): void
    {
        // Pengguna yang baru mendaftar tidak punya simpanan. Dashboard-nya
        // tidak boleh menampilkan angka palsu maupun daftar kosong tanpa jalan
        // keluar: rekomendasi wajib terisi supaya ada yang bisa ditandai, dan
        // jalur ke Studio Dapur tetap tersedia bagi semua Pengguna.
        $pembaca = User::factory()->create(['role' => UserRole::Pengguna]);

        Resep::factory()->count(3)->create(['status' => 'terbit']);
        Resep::factory()->create(['status' => 'draft']);

        $this->actingAs($pembaca)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->where('jumlahTersimpan', 0)
                ->where('tersimpan', fn ($items) => collect($items)->isEmpty())
                ->where('rekomendasi', fn ($items) => collect($items)->count() === 3)
                ->where('auth.permissions.access-studio', true));
    }

    /* ─────────────────────────── Pembersihan ─────────────────────────── */
    public function test_menghapus_pengguna_ikut_membersihkan_simpanannya(): void
    {
        $pengguna = User::factory()->create();
        $resep = Resep::factory()->create();

        $this->actingAs($pengguna)->post(route('simpanan.toggle', $resep->id));

        $pengguna->delete();

        $this->assertDatabaseCount('simpanan', 0);
    }

    public function test_menghapus_resep_ikut_membersihkan_simpanannya(): void
    {
        $pengguna = User::factory()->create();
        $resep = Resep::factory()->create();

        $this->actingAs($pengguna)->post(route('simpanan.toggle', $resep->id));

        $resep->delete();

        $this->assertDatabaseCount('simpanan', 0);
    }

    /**
     * Unique index ganda adalah penjaga integritas terakhir di bawah
     * `firstOrCreate`. Bila indeksnya gugur, aplikasi tetap terlihat benar
     * sampai seseorang mengklik dua kali dalam rentang milidetik.
     */
    public function test_database_menolak_simpanan_ganda(): void
    {
        $pengguna = User::factory()->create();
        $resep = Resep::factory()->create();

        Simpanan::create(['user_id' => $pengguna->id, 'resep_id' => $resep->id]);

        $this->expectException(QueryException::class);

        Simpanan::create(['user_id' => $pengguna->id, 'resep_id' => $resep->id]);
    }
}
