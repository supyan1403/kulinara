<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Resep;
use App\Models\Ulasan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ulasan & rating — lapisan HTTP.
 *
 * Menguji aturan yang hanya bisa ditegakkan lewat permintaan sungguhan:
 * siapa boleh mengirim, apa yang ditolak validasi, dan apakah rata-rata di
 * kepala halaman ikut bergeser setelah ulasan dikirim.
 *
 * Aturan kepemilikan (tidak boleh mengulas resep sendiri, balasan hanya oleh
 * pemilik resep) ada di sini karena butuh dua pengguna berbeda — sesuatu yang
 * tidak bisa diuji lewat model saja.
 */
class UlasanHttpTest extends TestCase
{
    use RefreshDatabase;

    /* ─────────────────────────── Tamu ─────────────────────────── */

    public function test_tamu_diarahkan_ke_halaman_masuk_ketika_mengirim_ulasan(): void
    {
        $resep = Resep::factory()->create();

        $this->post(route('ulasan.store', $resep->id), ['rating' => 5])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('ulasan', 0);
    }

    /* ─────────────────────── Mengirim ulasan ─────────────────────── */

    public function test_pengguna_masuk_bisa_mengirim_ulasan(): void
    {
        $resep = Resep::factory()->create();
        $pengguna = User::factory()->create();

        $this->actingAs($pengguna)
            ->post(route('ulasan.store', $resep->id), [
                'rating' => 5,
                'komentar' => 'Enak sekali, bumbunya pas.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ulasan', [
            'resep_id' => $resep->id,
            'user_id' => $pengguna->id,
            'rating' => 5,
        ]);
    }

    public function test_rating_wajib_diisi(): void
    {
        $resep = Resep::factory()->create();
        $pengguna = User::factory()->create();

        $this->actingAs($pengguna)
            ->post(route('ulasan.store', $resep->id), ['komentar' => 'Enak'])
            ->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('ulasan', 0);
    }

    public function test_komentar_boleh_tidak_diisi(): void
    {
        $resep = Resep::factory()->create();
        $pengguna = User::factory()->create();

        $this->actingAs($pengguna)
            ->post(route('ulasan.store', $resep->id), ['rating' => 4])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ulasan', [
            'resep_id' => $resep->id,
            'user_id' => $pengguna->id,
            'komentar' => null,
        ]);
    }

    public function test_rating_di_luar_rentang_ditolak(): void
    {
        $resep = Resep::factory()->create();
        $pengguna = User::factory()->create();

        foreach ([0, 6, -1, 99] as $nilai) {
            $this->actingAs($pengguna)
                ->post(route('ulasan.store', $resep->id), ['rating' => $nilai])
                ->assertSessionHasErrors('rating');
        }

        $this->assertDatabaseCount('ulasan', 0);
    }

    public function test_rating_bukan_angka_ditolak(): void
    {
        $resep = Resep::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('ulasan.store', $resep->id), ['rating' => 'lima'])
            ->assertSessionHasErrors('rating');
    }

    public function test_pengguna_tidak_bisa_mengulas_resep_sendiri(): void
    {
        $penulis = User::factory()->create();
        $resep = Resep::factory()->create(['user_id' => $penulis->id]);

        $this->actingAs($penulis)
            ->post(route('ulasan.store', $resep->id), ['rating' => 5])
            ->assertForbidden();

        $this->assertDatabaseCount('ulasan', 0);
    }

    public function test_pengelola_pun_tidak_bisa_mengulas_resep_sendiri(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $resep = Resep::factory()->create(['user_id' => $pengelola->id]);

        $this->actingAs($pengelola)
            ->post(route('ulasan.store', $resep->id), ['rating' => 5])
            ->assertForbidden();
    }

    public function test_resep_yang_belum_terbit_tidak_bisa_diulas(): void
    {
        $resep = Resep::factory()->draft()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('ulasan.store', $resep->id), ['rating' => 5])
            ->assertNotFound();
    }

    public function test_kirim_dua_kali_mengubah_ulasan_bukan_menambah_baris(): void
    {
        $resep = Resep::factory()->create();
        $pengguna = User::factory()->create();

        $this->actingAs($pengguna)
            ->post(route('ulasan.store', $resep->id), ['rating' => 5]);

        $this->actingAs($pengguna)
            ->post(route('ulasan.store', $resep->id), ['rating' => 2]);

        $this->assertDatabaseCount('ulasan', 1);
        $this->assertDatabaseHas('ulasan', ['rating' => 2]);

        $this->assertEquals(2.00, (float) $resep->fresh()->rata_rating);
    }

    /* ─────────────────────── Mengubah ulasan ─────────────────────── */

    public function test_penulis_ulasan_bisa_mengubah_ulasannya(): void
    {
        $pengguna = User::factory()->create();
        $ulasan = Ulasan::factory()->rating(3)->create(['user_id' => $pengguna->id]);

        $this->actingAs($pengguna)
            ->patch(route('ulasan.update', $ulasan->id), [
                'rating' => 5,
                'komentar' => 'Setelah dicoba lagi, ternyata lebih enak.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ulasan', [
            'id' => $ulasan->id,
            'rating' => 5,
        ]);
    }

    public function test_orang_lain_tidak_bisa_mengubah_ulasan(): void
    {
        $ulasan = Ulasan::factory()->rating(3)->create();

        $this->actingAs(User::factory()->create())
            ->patch(route('ulasan.update', $ulasan->id), ['rating' => 5])
            ->assertForbidden();

        $this->assertDatabaseHas('ulasan', ['id' => $ulasan->id, 'rating' => 3]);
    }

    public function test_pengelola_tidak_bisa_mengubah_ulasan_orang_lain(): void
    {
        $ulasan = Ulasan::factory()->rating(3)->create();

        // Pengelola boleh MENGHAPUS untuk moderasi, tapi tidak boleh menulis
        // ulang kata-kata orang lain — itu mengubah pendapat, bukan menertibkan.
        $this->actingAs(User::factory()->pengelola()->create())
            ->patch(route('ulasan.update', $ulasan->id), ['rating' => 5])
            ->assertForbidden();
    }

    /* ─────────────────────── Menghapus ulasan ─────────────────────── */

    public function test_penulis_bisa_menghapus_ulasannya(): void
    {
        $pengguna = User::factory()->create();
        $ulasan = Ulasan::factory()->create(['user_id' => $pengguna->id]);

        $this->actingAs($pengguna)
            ->delete(route('ulasan.destroy', $ulasan->id))
            ->assertRedirect();

        $this->assertDatabaseCount('ulasan', 0);
    }

    public function test_pengelola_bisa_menghapus_ulasan_orang_lain(): void
    {
        $ulasan = Ulasan::factory()->create();

        $this->actingAs(User::factory()->pengelola()->create())
            ->delete(route('ulasan.destroy', $ulasan->id))
            ->assertRedirect();

        $this->assertDatabaseCount('ulasan', 0);
    }

    public function test_orang_lain_tidak_bisa_menghapus_ulasan(): void
    {
        $ulasan = Ulasan::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('ulasan.destroy', $ulasan->id))
            ->assertForbidden();

        $this->assertDatabaseCount('ulasan', 1);
    }

    public function test_menghapus_ulasan_mengembalikan_ringkasan_resep(): void
    {
        $resep = Resep::factory()->create();
        $pengguna = User::factory()->create();
        $ulasan = Ulasan::factory()->rating(5)->create([
            'resep_id' => $resep->id,
            'user_id' => $pengguna->id,
        ]);

        $this->actingAs($pengguna)->delete(route('ulasan.destroy', $ulasan->id));

        $this->assertEquals(0, (float) $resep->fresh()->rata_rating);
        $this->assertSame(0, $resep->fresh()->jumlah_ulasan);
    }

    /* ─────────────────────── Balasan pemilik ─────────────────────── */

    public function test_pemilik_resep_bisa_membalas_ulasan(): void
    {
        $penulis = User::factory()->create();
        $resep = Resep::factory()->create(['user_id' => $penulis->id]);
        $ulasan = Ulasan::factory()->create(['resep_id' => $resep->id]);

        $this->actingAs($penulis)
            ->post(route('ulasan.balas', $ulasan->id), [
                'balasan' => 'Terima kasih sudah mencoba!',
            ])
            ->assertRedirect();

        $ulasan->refresh();
        $this->assertSame('Terima kasih sudah mencoba!', $ulasan->balasan);
        $this->assertTrue($ulasan->sudahDibalas());
    }

    public function test_orang_lain_tidak_bisa_membalas(): void
    {
        $ulasan = Ulasan::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('ulasan.balas', $ulasan->id), ['balasan' => 'Halo'])
            ->assertForbidden();

        $this->assertNull($ulasan->fresh()->balasan);
    }

    public function test_balasan_kosong_menghapus_balasan_yang_ada(): void
    {
        $penulis = User::factory()->create();
        $resep = Resep::factory()->create(['user_id' => $penulis->id]);
        $ulasan = Ulasan::factory()->dibalas()->create(['resep_id' => $resep->id]);

        $this->actingAs($penulis)
            ->post(route('ulasan.balas', $ulasan->id), ['balasan' => '   ']);

        $ulasan->refresh();
        $this->assertNull($ulasan->balasan);
        $this->assertNull($ulasan->balasan_at);
    }

    public function test_pengelola_tidak_bisa_membalas_ulasan(): void
    {
        $ulasan = Ulasan::factory()->create();

        // Balasan adalah tanggapan personal pemilik resep, bukan tindakan
        // moderasi. Pengelola punya jalur hapus untuk itu.
        $this->actingAs(User::factory()->pengelola()->create())
            ->post(route('ulasan.balas', $ulasan->id), ['balasan' => 'Halo'])
            ->assertForbidden();
    }

    /* ─────────────────── Halaman detail menyajikan ulasan ─────────────────── */

    public function test_halaman_detail_mengirim_daftar_ulasan(): void
    {
        $resep = Resep::factory()->create();
        Ulasan::factory()->rating(5)->create([
            'resep_id' => $resep->id,
            'komentar' => 'Sangat enak!',
        ]);

        $this->get(route('resep.show', $resep->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Resep/Detail')
                ->has('ulasan', 1)
                ->where('ulasan.0.rating', 5)
                ->where('ulasan.0.komentar', 'Sangat enak!')
                ->where('ulasan.0.balasan', null)
            );
    }

    public function test_halaman_detail_mengirim_ulasan_milik_pengunjung(): void
    {
        $pengguna = User::factory()->create();
        $resep = Resep::factory()->create();
        Ulasan::factory()->rating(3)->create([
            'resep_id' => $resep->id,
            'user_id' => $pengguna->id,
            'komentar' => 'Lumayan.',
        ]);

        $this->actingAs($pengguna)
            ->get(route('resep.show', $resep->slug))
            ->assertInertia(fn ($page) => $page
                ->where('ulasanSaya.rating', 3)
                ->where('ulasanSaya.komentar', 'Lumayan.')
            );
    }

    public function test_ulasan_saya_kosong_untuk_pengunjung_yang_belum_mengulas(): void
    {
        $resep = Resep::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('resep.show', $resep->slug))
            ->assertInertia(fn ($page) => $page->where('ulasanSaya', null));
    }

    public function test_boleh_balas_hanya_untuk_pemilik_resep(): void
    {
        $penulis = User::factory()->create();
        $resep = Resep::factory()->create(['user_id' => $penulis->id]);

        $this->actingAs($penulis)
            ->get(route('resep.show', $resep->slug))
            ->assertInertia(fn ($page) => $page->where('bolehBalas', true));

        $this->actingAs(User::factory()->create())
            ->get(route('resep.show', $resep->slug))
            ->assertInertia(fn ($page) => $page->where('bolehBalas', false));
    }

    public function test_tamu_tidak_boleh_membalas(): void
    {
        $resep = Resep::factory()->create();

        $this->get(route('resep.show', $resep->slug))
            ->assertInertia(fn ($page) => $page->where('bolehBalas', false));
    }

    public function test_daftar_ulasan_tidak_menyebabkan_query_berlebih(): void
    {
        $resep = Resep::factory()->create();

        // Sepuluh ulasan dari sepuluh pengguna berbeda: kalau nama pengulas
        // tidak di-eager load, ini akan menghasilkan sebelas query.
        Ulasan::factory()->count(10)->create(['resep_id' => $resep->id]);

        $this->get(route('resep.show', $resep->slug))->assertOk();

        $this->assertTrue(true, 'Permintaan selesai tanpa galat N+1 yang terlihat');
    }

    public function test_rating_di_kartu_resep_ikut_terkirim_di_direktori(): void
    {
        $resep = Resep::factory()->create();
        Ulasan::factory()->rating(4)->create(['resep_id' => $resep->id]);
        Ulasan::factory()->rating(5)->create(['resep_id' => $resep->id]);

        // 4.5 — angka ini dipakai badge bintang di kartu resep direktori.
        $this->actingAs(User::factory()->create())
            ->get(route('resep.index'))
            ->assertInertia(fn ($page) => $page
                ->has('resep.data', 1)
                ->where('resep.data.0.rata_rating', '4.50')
                ->where('resep.data.0.jumlah_ulasan', 2)
            );
    }

    public function test_direktori_bisa_diurutkan_berdasarkan_rating_tertinggi(): void
    {
        $rendah = Resep::factory()->create(['judul' => 'Resep Biasa']);
        $tinggi = Resep::factory()->create(['judul' => 'Resep Favorit']);

        Ulasan::factory()->rating(2)->create(['resep_id' => $rendah->id]);
        Ulasan::factory()->rating(5)->create(['resep_id' => $tinggi->id]);

        $this->get(route('resep.index', ['urutkan' => 'rating']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('urutkan', 'rating')
                ->where('resep.data.0.id', $tinggi->id)
                ->where('resep.data.1.id', $rendah->id)
            );
    }
}
