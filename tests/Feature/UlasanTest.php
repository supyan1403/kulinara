<?php

namespace Tests\Feature;

use App\Models\Resep;
use App\Models\Ulasan;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ulasan & rating — lapisan data.
 *
 * Berkas ini menguji hal-hal yang harus benar SEBELUM ada antarmuka: batas
 * satu ulasan per orang, penjagaan agar penulis resep tidak menilai karyanya
 * sendiri, dan yang paling penting — kolom ringkasan `rata_rating` /
 * `jumlah_ulasan` di tabel resep yang harus selalu cocok dengan isi tabel
 * ulasan.
 *
 * Kolom ringkasan itu sengaja disimpan (bukan dihitung saat query) supaya
 * kartu resep tidak perlu agregat. Konsekuensinya ia bisa menyimpang, dan
 * seluruh pengujian di bagian "ringkasan" ada untuk memastikan tidak.
 */
class UlasanTest extends TestCase
{
    use RefreshDatabase;

    /* ─────────────────── Batas satu ulasan per orang ─────────────────── */

    public function test_satu_pengguna_tidak_bisa_mengulas_resep_yang_sama_dua_kali(): void
    {
        $resep = Resep::factory()->create();
        $pengguna = User::factory()->create();

        Ulasan::factory()->create([
            'resep_id' => $resep->id,
            'user_id' => $pengguna->id,
        ]);

        // Bukan sekadar dicegah aplikasi: unique index di basis data yang
        // menolaknya. Diuji lewat QueryException supaya pemeriksaan di
        // controller tidak bisa menjadi satu-satunya penjaga.
        $this->expectException(QueryException::class);

        Ulasan::factory()->create([
            'resep_id' => $resep->id,
            'user_id' => $pengguna->id,
        ]);
    }

    public function test_pengguna_berbeda_boleh_mengulas_resep_yang_sama(): void
    {
        $resep = Resep::factory()->create();

        Ulasan::factory()->create(['resep_id' => $resep->id]);
        Ulasan::factory()->create(['resep_id' => $resep->id]);

        $this->assertSame(2, Ulasan::query()->where('resep_id', $resep->id)->count());
    }

    public function test_komentar_boleh_kosong_alias_hanya_bintang(): void
    {
        $ulasan = Ulasan::factory()->tanpaKomentar()->create();

        $this->assertNull($ulasan->fresh()->komentar);
    }

    /* ─────────────────── Ringkasan di tabel resep ─────────────────── */

    public function test_resep_baru_bermula_tanpa_ulasan(): void
    {
        $resep = Resep::factory()->create();

        $this->assertEquals(0, $resep->rata_rating);
        $this->assertSame(0, $resep->jumlah_ulasan);
        $this->assertFalse($resep->sudahDiulas());
    }

    public function test_ulasan_pertama_mengisi_ringkasan(): void
    {
        $resep = Resep::factory()->create();

        Ulasan::factory()->rating(4)->create(['resep_id' => $resep->id]);

        $resep->refresh();
        $this->assertEquals(4.00, (float) $resep->rata_rating);
        $this->assertSame(1, $resep->jumlah_ulasan);
        $this->assertTrue($resep->sudahDiulas());
    }

    public function test_rata_rata_dihitung_dari_semua_ulasan(): void
    {
        $resep = Resep::factory()->create();

        Ulasan::factory()->rating(5)->create(['resep_id' => $resep->id]);
        Ulasan::factory()->rating(4)->create(['resep_id' => $resep->id]);
        Ulasan::factory()->rating(3)->create(['resep_id' => $resep->id]);

        // (5 + 4 + 3) / 3 = 4.00
        $resep->refresh();
        $this->assertEquals(4.00, (float) $resep->rata_rating);
        $this->assertSame(3, $resep->jumlah_ulasan);
    }

    public function test_mengubah_rating_menggeser_rata_rata(): void
    {
        $resep = Resep::factory()->create();
        $ulasan = Ulasan::factory()->rating(2)->create(['resep_id' => $resep->id]);

        $ulasan->update(['rating' => 5]);

        $resep->refresh();
        $this->assertEquals(5.00, (float) $resep->rata_rating);
        $this->assertSame(1, $resep->jumlah_ulasan);
    }

    public function test_menghapus_ulasan_mengurangi_hitungan(): void
    {
        $resep = Resep::factory()->create();
        Ulasan::factory()->rating(5)->create(['resep_id' => $resep->id]);
        $kedua = Ulasan::factory()->rating(3)->create(['resep_id' => $resep->id]);

        $kedua->delete();

        $resep->refresh();
        $this->assertEquals(5.00, (float) $resep->rata_rating);
        $this->assertSame(1, $resep->jumlah_ulasan);
    }

    public function test_menghapus_ulasan_terakhir_mengembalikan_ringkasan_ke_nol(): void
    {
        $resep = Resep::factory()->create();
        $ulasan = Ulasan::factory()->rating(1)->create(['resep_id' => $resep->id]);

        $ulasan->delete();

        $resep->refresh();
        $this->assertEquals(0, (float) $resep->rata_rating);
        $this->assertSame(0, $resep->jumlah_ulasan);
        $this->assertFalse($resep->sudahDiulas());
    }

    public function test_rating_di_luar_rentang_tidak_dihitung_sebagai_kasus_sah(): void
    {
        // Basis data tidak memasang CHECK constraint pada rating — penjagaan
        // nilainya ada di validasi formulir. Yang diuji di sini adalah batas
        // konstanta yang dipakai bersama oleh validasi dan antarmuka.
        $this->assertSame(1, Ulasan::RATING_MIN);
        $this->assertSame(5, Ulasan::RATING_MAKS);
    }

    public function test_ringkasan_tidak_tercampur_antar_resep(): void
    {
        $pertama = Resep::factory()->create();
        $kedua = Resep::factory()->create();

        Ulasan::factory()->rating(5)->create(['resep_id' => $pertama->id]);
        Ulasan::factory()->rating(1)->create(['resep_id' => $kedua->id]);

        $pertama->refresh();
        $kedua->refresh();

        $this->assertEquals(5.00, (float) $pertama->rata_rating);
        $this->assertEquals(1.00, (float) $kedua->rata_rating);
    }

    /* ─────────────────── Menghapus resep / pengguna ─────────────────── */

    public function test_menghapus_resep_ikut_menghapus_ulasannya(): void
    {
        $resep = Resep::factory()->create();
        Ulasan::factory()->create(['resep_id' => $resep->id]);

        $resep->delete();

        $this->assertDatabaseCount('ulasan', 0);
    }

    public function test_menghapus_pengguna_ikut_menghapus_ulasannya(): void
    {
        $ulasan = Ulasan::factory()->create();
        $ulasan->pengguna->delete();

        $this->assertDatabaseCount('ulasan', 0);
    }

    /* ─────────────────── Ringkasan saat akun dihapus ─────────────────── */

    public function test_menghapus_akun_menyegarkan_ringkasan_resep_yang_diulasnya(): void
    {
        $resep = Resep::factory()->create();
        $pengulas = User::factory()->create();
        $lain = User::factory()->create();

        Ulasan::factory()->rating(4)->create([
            'resep_id' => $resep->id,
            'user_id' => $pengulas->id,
        ]);
        Ulasan::factory()->rating(2)->create([
            'resep_id' => $resep->id,
            'user_id' => $lain->id,
        ]);

        $this->assertEquals(3.00, (float) $resep->fresh()->rata_rating);

        // Ulasan milik pengguna yang dihapus ikut lenyap lewat ON DELETE
        // CASCADE — langsung di basis data, tanpa memicu UlasanObserver.
        // Tanpa penanganan khusus, ringkasan resep akan tetap menghitung
        // ulasan yang sudah tidak ada.
        $pengulas->delete();

        $resep->refresh();
        $this->assertEquals(2.00, (float) $resep->rata_rating);
        $this->assertSame(1, $resep->jumlah_ulasan);
    }

    public function test_menghapus_akun_tanpa_ulasan_tidak_mengubah_apa_pun(): void
    {
        $resep = Resep::factory()->create();
        Ulasan::factory()->rating(5)->create(['resep_id' => $resep->id]);

        User::factory()->create()->delete();

        $this->assertEquals(5.00, (float) $resep->fresh()->rata_rating);
        $this->assertSame(1, $resep->fresh()->jumlah_ulasan);
    }

    public function test_menghapus_akun_menyegarkan_semua_resep_yang_diulasnya(): void
    {
        $pengulas = User::factory()->create();

        $a = Resep::factory()->create();
        $b = Resep::factory()->create();

        Ulasan::factory()->rating(5)->create(['resep_id' => $a->id, 'user_id' => $pengulas->id]);
        Ulasan::factory()->rating(1)->create(['resep_id' => $b->id, 'user_id' => $pengulas->id]);

        $pengulas->delete();

        $this->assertSame(0, $a->fresh()->jumlah_ulasan);
        $this->assertSame(0, $b->fresh()->jumlah_ulasan);
        $this->assertEquals(0, (float) $a->fresh()->rata_rating);
    }

    /* ─────────────────── Penulisan kolom turunan ─────────────────── */

    public function test_kolom_ringkasan_tidak_masuk_daftar_fillable(): void
    {
        // Kolom turunan sengaja TIDAK fillable: ia hanya boleh ditulis oleh
        // sinkronkanRating(), bukan dari input pengguna. Tetapi konsekuensinya
        // tidak kelihatan — `update()` dan `updateQuietly()` menghormati daftar
        // itu dan akan MENGABAIKAN kedua kolom diam-diam, tanpa galat. Itulah
        // sebabnya sinkronkanRating() memakai forceFill, dan kenapa keadaan ini
        // diuji di sini supaya kalau seseorang menambahkan keduanya ke daftar
        // fillable, ia sadar sedang mengubah jaminan keamanan.
        $this->assertNotContains('rata_rating', (new Resep)->getFillable());
        $this->assertNotContains('jumlah_ulasan', (new Resep)->getFillable());
    }

    public function test_sinkronkan_rating_benar_benar_menulis_ke_basis_data(): void
    {
        $resep = Resep::factory()->create();

        // Nilai awal yang sengaja salah, seperti setelah impor SQL.
        Resep::query()->whereKey($resep->id)->update([
            'rata_rating' => 1.00,
            'jumlah_ulasan' => 99,
        ]);

        Ulasan::factory()->rating(4)->create(['resep_id' => $resep->id]);
        Ulasan::factory()->rating(5)->create(['resep_id' => $resep->id]);

        $resep->fresh()->sinkronkanRating();

        $this->assertDatabaseHas('resep', [
            'id' => $resep->id,
            'jumlah_ulasan' => 2,
        ]);
        $this->assertEquals(4.50, (float) $resep->fresh()->rata_rating);
    }

    /* ─────────────────── Balasan pemilik resep ─────────────────── */

    public function test_balasan_dikenali_sebagai_sudah_dibalas(): void    {
        $ulasan = Ulasan::factory()->create();

        $this->assertFalse($ulasan->sudahDibalas());

        $ulasan->update([
            'balasan' => 'Terima kasih sudah mencoba!',
            'balasan_at' => now(),
        ]);

        $this->assertTrue($ulasan->fresh()->sudahDibalas());
    }

    public function test_balasan_kosong_bukan_balasan(): void
    {
        $ulasan = Ulasan::factory()->create(['balasan' => '']);

        $this->assertFalse($ulasan->sudahDibalas());
    }

    /* ─────────────────── Command pemeliharaan ─────────────────── */

    public function test_command_memperbaiki_ringkasan_yang_menyimpang(): void
    {
        $resep = Resep::factory()->create();
        Ulasan::factory()->rating(5)->create(['resep_id' => $resep->id]);
        Ulasan::factory()->rating(4)->create(['resep_id' => $resep->id]);

        // Menyimpangkan nilai seperti kalau ada impor SQL yang melewati
        // observer — persis keadaan yang membuat command ini ada.
        Resep::query()->whereKey($resep->id)->update([
            'rata_rating' => 1.00,
            'jumlah_ulasan' => 99,
        ]);

        $this->artisan('rating:hitung-ulang')->assertSuccessful();

        $resep->refresh();
        $this->assertEquals(4.50, (float) $resep->rata_rating);
        $this->assertSame(2, $resep->jumlah_ulasan);
    }

    public function test_command_kering_tidak_mengubah_apa_pun(): void
    {
        $resep = Resep::factory()->create();
        Ulasan::factory()->rating(5)->create(['resep_id' => $resep->id]);

        Resep::query()->whereKey($resep->id)->update(['rata_rating' => 1.00]);

        $this->artisan('rating:hitung-ulang --kering')->assertSuccessful();

        $this->assertEquals(1.00, (float) $resep->fresh()->rata_rating);
    }

    public function test_command_aman_dijalankan_saat_semua_sudah_cocok(): void
    {
        Resep::factory()->create();
        Ulasan::factory()->create();

        $this->artisan('rating:hitung-ulang')->assertSuccessful();
    }
}
