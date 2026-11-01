<?php

namespace Tests\Feature;

use App\Enums\SatuanDasar;
use App\Models\Bahan;
use App\Models\Resep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class StudioRecipeCrudTest extends TestCase
{
    use RefreshDatabase;

    /* ─────────────────────────── Akses ─────────────────────────── */

    public function test_pengguna_bisa_membuka_studio_dapur(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('studio.index'))
            ->assertOk();
    }

    public function test_chef_hanya_melihat_resep_miliknya(): void
    {
        $saya = User::factory()->create();
        $lain = User::factory()->create();

        Resep::factory()->for($saya, 'penulis')->create(['judul' => 'Resep Milik Saya']);
        Resep::factory()->for($lain, 'penulis')->create(['judul' => 'Resep Orang Lain']);

        $this->actingAs($saya)
            ->get(route('studio.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Studio/Index')
                ->has('resep', 1)
                ->where('resep.0.judul', 'Resep Milik Saya'));
    }

    public function test_pengelola_melihat_semua_resep(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $chef = User::factory()->create();

        Resep::factory()->for($chef, 'penulis')->count(2)->create();

        $this->actingAs($pengelola)
            ->get(route('studio.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->has('resep', 2));
    }

    /**
     * Halaman formulir mengirim rujukan bahan untuk autocomplete — dan
     * memastikan pemetaan enum (kategori, satuan_dasar) tidak melempar
     * galat saat serialisasi ke Inertia.
     */
    public function test_formulir_mengirim_rujukan_bahan(): void
    {
        $chef = User::factory()->create();
        Bahan::factory()->cair()->create(['nama' => 'Santan kental']);

        $this->actingAs($chef)
            ->get(route('studio.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Studio/Form')
                ->has('bahanTersedia', 1)
                ->where('bahanTersedia.0.nama', 'Santan kental')
                ->where('bahanTersedia.0.satuan_dasar', 'ml'));
    }

    /* ─────────────────────────── Buat ─────────────────────────── */

    public function test_chef_bisa_membuat_resep_baru_dengan_slug_otomatis(): void
    {
        $chef = User::factory()->create();

        $this->actingAs($chef)
            ->post(route('studio.store'), $this->dataValid())
            ->assertRedirect(route('studio.index'));

        $resep = Resep::query()->firstOrFail();

        $this->assertSame($chef->id, $resep->user_id);
        $this->assertSame('sayur-lodeh-terong-muda', $resep->slug);
        $this->assertSame('draft', $resep->status->value);
        $this->assertCount(2, $resep->bahan);
        $this->assertCount(3, $resep->langkah);
    }

    public function test_membuat_resep_dengan_data_tidak_lengkap_ditolak(): void
    {
        $chef = User::factory()->create();

        $this->actingAs($chef)
            ->post(route('studio.store'), [
                'judul' => 'abc',
                'bahan' => [],
                'langkah' => [],
            ])
            ->assertSessionHasErrors([
                'judul',
                'deskripsi',
                'kategori',
                'tingkat_kesulitan',
                'waktu_masak_menit',
                'porsi',
                // Gizi TIDAK lagi diminta di sini: mode bawaan adalah
                // otomatis, jadi empat kolom gizi dihitung server dari
                // daftar bahan. Kewajiban mengisi gizi hanya berlaku pada
                // mode manual — lihat tes terpisah di bawah.
                'bahan',
                'langkah',
            ]);

        $this->assertSame(0, Resep::query()->count());
    }

    /**
     * Mode manual tetap menuntut gizi diisi tangan — jalan keluar ketika
     * ada bahan yang tak bisa dihitung otomatis (mis. "secukupnya").
     */
    public function test_mode_manual_wajib_mengisi_gizi(): void
    {
        $chef = User::factory()->create();

        $data = collect($this->dataValid())
            ->except(['energi_kkal', 'protein_g', 'lemak_g', 'karbohidrat_g'])
            ->put('gizi_mode', 'manual')
            ->all();

        $this->actingAs($chef)
            ->post(route('studio.store'), $data)
            ->assertSessionHasErrors(['energi_kkal', 'protein_g', 'lemak_g', 'karbohidrat_g']);

        $this->assertSame(0, Resep::query()->count());
    }

    /**
     * Mode otomatis menghitung gizi dari daftar bahan — nilai kiriman
     * penulis diabaikan, dan hasilnya dibagi jumlah porsi.
     */
    public function test_mode_otomatis_menghitung_gizi_dari_bahan(): void
    {
        $chef = User::factory()->create();

        Bahan::factory()->create([
            'nama' => 'Beras putih',
            'energi_kkal' => 360, 'protein_g' => 10, 'lemak_g' => 0, 'karbohidrat_g' => 0,
        ]);

        $this->actingAs($chef)
            ->post(route('studio.store'), $this->dataValid([
                'gizi_mode' => 'otomatis',
                'porsi' => 2,
                // Kirim angka gizi bohongan — harus ditimpa hasil hitungan.
                'energi_kkal' => 1, 'protein_g' => 1, 'lemak_g' => 1, 'karbohidrat_g' => 1,
                'bahan' => [
                    ['nama' => 'Beras putih', 'jumlah' => 300, 'satuan' => 'gram'],
                ],
            ]))
            ->assertRedirect(route('studio.index'));

        $resep = Resep::query()->firstOrFail();

        // 300 g beras × 360/100 = 1080 kkal, dibagi 2 porsi = 540.
        $this->assertSame('otomatis', $resep->gizi_mode);
        $this->assertEqualsWithDelta(540, (float) $resep->energi_kkal, 0.01);
        $this->assertEqualsWithDelta(15, (float) $resep->protein_g, 0.01);
    }

    /**
     * Mode manual mempertahankan angka yang dikirim penulis — tidak
     * ditimpa hitungan server, walau bahan bisa dikenali rujukan.
     */
    public function test_mode_manual_mempertahankan_gizi_kiriman(): void
    {
        $chef = User::factory()->create();

        Bahan::factory()->create([
            'nama' => 'Beras putih',
            'energi_kkal' => 360,
        ]);

        $this->actingAs($chef)
            ->post(route('studio.store'), $this->dataValid([
                'gizi_mode' => 'manual',
                'energi_kkal' => 180.5,
                'bahan' => [
                    ['nama' => 'Beras putih', 'jumlah' => 300, 'satuan' => 'gram'],
                ],
            ]))
            ->assertRedirect(route('studio.index'));

        $resep = Resep::query()->firstOrFail();

        // Bukan 1080 (hitungan otomatis) — tetap 180,5 kiriman penulis.
        $this->assertSame('manual', $resep->gizi_mode);
        $this->assertEqualsWithDelta(180.5, (float) $resep->energi_kkal, 0.01);
    }

    /**
     * Mengubah porsi pada resep mode otomatis menghitung ulang gizi per
     * porsi — bukan sekadar menyimpan angka gizi yang sama.
     */
    public function test_mengubah_porsi_menghitung_ulang_gizi_otomatis(): void
    {
        $chef = User::factory()->create();
        $resep = Resep::factory()->for($chef, 'penulis')->create([
            'gizi_mode' => 'otomatis',
            'porsi' => 2,
        ]);

        Bahan::factory()->create([
            'nama' => 'Beras putih',
            'energi_kkal' => 360,
        ]);

        $this->actingAs($chef)
            ->put(route('studio.update', $resep), $this->dataValid([
                'gizi_mode' => 'otomatis',
                'porsi' => 4,
                'bahan' => [
                    ['nama' => 'Beras putih', 'jumlah' => 400, 'satuan' => 'gram'],
                ],
            ]))
            ->assertRedirect(route('studio.index'));

        $resep->refresh();

        // 400 g × 360/100 = 1440 kkal, dibagi 4 porsi = 360.
        $this->assertEqualsWithDelta(360, (float) $resep->energi_kkal, 0.01);
    }

    /**
     * Bahan yang dirujuk lewat `bahan_id` ikut dihitung — memastikan
     * pilihan autocomplete di Studio benar-benar tersambung ke rujukan.
     */
    public function test_bahan_dirujuk_lewat_bahan_id_ikut_dihitung(): void
    {
        $chef = User::factory()->create();
        $bahan = Bahan::factory()->create([
            'nama' => 'Santan kental',
            'satuan_dasar' => SatuanDasar::Mililiter,
            'energi_kkal' => 190,
            'faktor_densitas' => 1,
        ]);

        $this->actingAs($chef)
            ->post(route('studio.store'), $this->dataValid([
                'gizi_mode' => 'otomatis',
                'porsi' => 1,
                'bahan' => [
                    ['nama' => 'santan beda penulisan', 'jumlah' => 200, 'satuan' => 'ml', 'bahan_id' => $bahan->id],
                ],
            ]))
            ->assertRedirect(route('studio.index'));

        $resep = Resep::query()->firstOrFail();

        // 200 ml × 190/100 = 380 kkal per porsi.
        $this->assertEqualsWithDelta(380, (float) $resep->energi_kkal, 0.01);
    }

    public function test_chef_tidak_bisa_menandai_resep_terverifikasi(): void
    {
        $chef = User::factory()->create();

        $this->actingAs($chef)
            ->post(route('studio.store'), $this->dataValid(['status' => 'terverifikasi']))
            ->assertSessionHasErrors('status');

        $this->assertSame(0, Resep::query()->count());
    }

    public function test_pengelola_bisa_menandai_resep_terverifikasi(): void
    {
        $pengelola = User::factory()->pengelola()->create();

        $this->actingAs($pengelola)
            ->post(route('studio.store'), $this->dataValid(['status' => 'terverifikasi']))
            ->assertRedirect(route('studio.index'));

        $this->assertSame('terverifikasi', Resep::query()->firstOrFail()->status->value);
    }

    public function test_judul_sama_mendapat_slug_berekor(): void
    {
        $chef = User::factory()->create();

        foreach ([1, 2] as $n) {
            $this->actingAs($chef)
                ->post(route('studio.store'), $this->dataValid())
                ->assertRedirect(route('studio.index'));
        }

        $slug = Resep::query()->orderBy('id')->pluck('slug')->all();

        $this->assertSame(['sayur-lodeh-terong-muda', 'sayur-lodeh-terong-muda-2'], $slug);
    }

    /* ─────────────────────────── Ubah ─────────────────────────── */

    public function test_chef_bisa_mengubah_resep_miliknya(): void
    {
        $chef = User::factory()->create();
        $resep = Resep::factory()->for($chef, 'penulis')->create();

        $this->actingAs($chef)
            ->put(route('studio.update', $resep), $this->dataValid([
                'judul' => 'Sayur Lodeh Terong Premium',
                'porsi' => 6,
            ]))
            ->assertRedirect(route('studio.index'));

        $resep->refresh();

        $this->assertSame('Sayur Lodeh Terong Premium', $resep->judul);
        $this->assertSame(6, (int) $resep->porsi);
        $this->assertSame($chef->id, $resep->user_id);
    }

    public function test_chef_tidak_bisa_mengubah_resep_orang_lain(): void
    {
        $saya = User::factory()->create();
        $lain = User::factory()->create();
        $resep = Resep::factory()->for($lain, 'penulis')->create();

        $this->actingAs($saya)
            ->put(route('studio.update', $resep), $this->dataValid())
            ->assertStatus(403);
    }

    public function test_pengelola_bisa_mengubah_resep_orang_lain(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $chef = User::factory()->create();
        $resep = Resep::factory()->for($chef, 'penulis')->create(['status' => 'terbit']);

        $this->actingAs($pengelola)
            ->put(route('studio.update', $resep), $this->dataValid(['status' => 'terverifikasi']))
            ->assertRedirect(route('studio.index'));

        $this->assertSame('terverifikasi', $resep->refresh()->status->value);
    }

    public function test_chef_tetap_bisa_menyimpan_resep_yang_sudah_diverifikasi(): void
    {
        $chef = User::factory()->create();
        $resep = Resep::factory()->for($chef, 'penulis')->create(['status' => 'terverifikasi']);

        // Tanpa pengecualian ini, status terverifikasi akan ditolak aturan validasi.
        $this->actingAs($chef)
            ->put(route('studio.update', $resep), $this->dataValid(['status' => 'terverifikasi']))
            ->assertRedirect(route('studio.index'));

        $this->assertSame('terverifikasi', $resep->refresh()->status->value);
    }

    public function test_mengubah_resep_tanpa_unggahan_baru_mempertahankan_gambar(): void
    {
        $chef = User::factory()->create();
        $resep = Resep::factory()->for($chef, 'penulis')->create([
            'gambar' => 'images/resep/gambar-lama.jpg',
        ]);

        $this->actingAs($chef)
            ->put(route('studio.update', $resep), $this->dataValid(['gambar' => null]))
            ->assertRedirect(route('studio.index'));

        $this->assertSame('images/resep/gambar-lama.jpg', $resep->refresh()->gambar);
    }

    /* ─────────────────────────── Hapus ─────────────────────────── */

    public function test_chef_bisa_menghapus_resep_miliknya(): void
    {
        $chef = User::factory()->create();
        $resep = Resep::factory()->for($chef, 'penulis')->create();

        $this->actingAs($chef)
            ->delete(route('studio.destroy', $resep))
            ->assertRedirect(route('studio.index'));

        $this->assertDatabaseMissing('resep', ['id' => $resep->id]);
    }

    public function test_chef_tidak_bisa_menghapus_resep_orang_lain(): void
    {
        $saya = User::factory()->create();
        $lain = User::factory()->create();
        $resep = Resep::factory()->for($lain, 'penulis')->create();

        $this->actingAs($saya)
            ->delete(route('studio.destroy', $resep))
            ->assertStatus(403);

        $this->assertDatabaseHas('resep', ['id' => $resep->id]);
    }

    /* ─────────────────────────── Gambar ─────────────────────────── */

    public function test_gambar_terunggah_ke_folder_publik(): void
    {
        $chef = User::factory()->create();

        $this->actingAs($chef)
            ->post(route('studio.store'), $this->dataValid([
                'gambar' => UploadedFile::fake()->create('lodeh.jpg', 200, 'image/jpeg'),
            ]))
            ->assertRedirect(route('studio.index'));

        $gambar = Resep::query()->firstOrFail()->gambar;

        // Path relatif dari root, sama seperti foto bawaan images/landing/.
        $this->assertStringStartsWith('images/resep/', $gambar);
        $this->assertFileExists(public_path($gambar));

        File::delete(public_path($gambar));
    }

    public function test_mengganti_gambar_menghapus_berkas_lama(): void
    {
        File::ensureDirectoryExists(public_path('images/resep'));
        File::put(public_path('images/resep/lama.jpg'), 'isi-lama');

        $chef = User::factory()->create();
        $resep = Resep::factory()->for($chef, 'penulis')->create([
            'gambar' => 'images/resep/lama.jpg',
        ]);

        $this->actingAs($chef)
            ->put(route('studio.update', $resep), $this->dataValid([
                'gambar' => UploadedFile::fake()->create('baru.jpg', 150, 'image/jpeg'),
            ]))
            ->assertRedirect(route('studio.index'));

        $resep->refresh();

        $this->assertFileDoesNotExist(public_path('images/resep/lama.jpg'));
        $this->assertFileExists(public_path($resep->gambar));

        File::delete(public_path($resep->gambar));
    }

    public function test_menghapus_resep_juga_menghapus_gambarnya(): void
    {
        File::ensureDirectoryExists(public_path('images/resep'));
        File::put(public_path('images/resep/resep.jpg'), 'isi');

        $chef = User::factory()->create();
        $resep = Resep::factory()->for($chef, 'penulis')->create([
            'gambar' => 'images/resep/resep.jpg',
        ]);

        $this->actingAs($chef)
            ->delete(route('studio.destroy', $resep))
            ->assertRedirect(route('studio.index'));

        $this->assertFileDoesNotExist(public_path('images/resep/resep.jpg'));
    }

    public function test_menghapus_resep_tidak_menyentuh_foto_bawaan(): void
    {
        // Foto di images/landing/ dipakai bersama oleh halaman utama.
        File::ensureDirectoryExists(public_path('images/landing'));
        File::put(public_path('images/landing/foto-bersama.jpg'), 'isi');

        $chef = User::factory()->create();
        $resep = Resep::factory()->for($chef, 'penulis')->create([
            'gambar' => 'images/landing/foto-bersama.jpg',
        ]);

        $this->actingAs($chef)
            ->delete(route('studio.destroy', $resep))
            ->assertRedirect(route('studio.index'));

        $this->assertFileExists(public_path('images/landing/foto-bersama.jpg'));

        File::delete(public_path('images/landing/foto-bersama.jpg'));
    }

    /* ─────────────────── Standarisasi & rendemen (Fase 2) ─────────────────── */

    /**
     * Field standar (hasil_total, satuan_hasil, alat) tersimpan saat
     * resep dibuat — dan gizi_cakupan diisi otomatis dari hitungan.
     */
    public function test_field_standar_tersimpan(): void
    {
        $chef = User::factory()->create();

        Bahan::factory()->create([
            'nama' => 'Beras putih',
            'energi_kkal' => 360,
        ]);

        $this->actingAs($chef)
            ->post(route('studio.store'), $this->dataValid([
                'gizi_mode' => 'otomatis',
                'porsi' => 2,
                'hasil_total' => 900,
                'satuan_hasil' => 'gram',
                'alat' => ['Panci', 'Kompor'],
                'bahan' => [
                    ['nama' => 'Beras putih', 'jumlah' => 300, 'satuan' => 'gram'],
                ],
            ]))
            ->assertRedirect(route('studio.index'));

        $resep = Resep::query()->firstOrFail();

        $this->assertEqualsWithDelta(900, (float) $resep->hasil_total, 0.01);
        $this->assertSame('gram', $resep->satuan_hasil->value);
        $this->assertSame(['Panci', 'Kompor'], $resep->alat);
        // Cakupan 100% karena satu-satunya bahan terhitung penuh.
        $this->assertSame(100, $resep->gizi_cakupan);
        $this->assertTrue($resep->standarTerpenuhi());
    }

    /**
     * Mode manual membuat gizi_cakupan 0 dan standar TIDAK terpenuhi —
     * gizi yang diisi tangan tidak bisa diverifikasi, jadi tidak boleh
     * mendapat badge "Standar".
     */
    public function test_mode_manual_tidak_memenuhi_standar(): void
    {
        $chef = User::factory()->create();

        $this->actingAs($chef)
            ->post(route('studio.store'), $this->dataValid([
                'gizi_mode' => 'manual',
                'energi_kkal' => 100, 'protein_g' => 1, 'lemak_g' => 1, 'karbohidrat_g' => 1,
                'hasil_total' => 900,
                'satuan_hasil' => 'gram',
            ]))
            ->assertRedirect(route('studio.index'));

        $resep = Resep::query()->firstOrFail();

        $this->assertSame(0, $resep->gizi_cakupan);
        $this->assertFalse($resep->standarTerpenuhi());
        $this->assertNull($resep->rendemen());
    }

    /**
     * Rendemen = hasil_total ÷ total gram bahan × 100. Kasus nyata:
     * 300 g beras + 400 ml santan (densitas 1 → 400 g) = 700 g masukan,
     * hasil akhir 900 g → rendemen 128,6% (memang bisa >100% karena
     * penyerapan air santan).
     */
    public function test_rendemen_dihitung_dari_hasil_dan_total_bahan(): void
    {
        $chef = User::factory()->create();

        Bahan::factory()->create([
            'nama' => 'Beras putih',
            'energi_kkal' => 360,
        ]);
        Bahan::factory()->cair()->create([
            'nama' => 'Santan kental',
            'energi_kkal' => 190,
            'faktor_densitas' => 1,
        ]);

        $this->actingAs($chef)
            ->post(route('studio.store'), $this->dataValid([
                'gizi_mode' => 'otomatis',
                'porsi' => 2,
                'hasil_total' => 900,
                'satuan_hasil' => 'gram',
                'bahan' => [
                    ['nama' => 'Beras putih', 'jumlah' => 300, 'satuan' => 'gram'],
                    ['nama' => 'Santan kental', 'jumlah' => 400, 'satuan' => 'ml'],
                ],
            ]))
            ->assertRedirect(route('studio.index'));

        $resep = Resep::query()->firstOrFail();

        // 300 + 400 = 700 g masukan. 900/700*100 = 128,6%.
        $this->assertEqualsWithDelta(128.6, $resep->rendemen(), 0.1);
    }

    /**
     * Tanpa `hasil_total`, rendemen null meski gizi otomatis penuh —
     * tidak ada angka hasil untuk dibandingkan.
     */
    public function test_rendemen_null_tanpa_hasil_total(): void
    {
        $chef = User::factory()->create();

        Bahan::factory()->create(['nama' => 'Beras putih', 'energi_kkal' => 360]);

        $this->actingAs($chef)
            ->post(route('studio.store'), $this->dataValid([
                'gizi_mode' => 'otomatis',
                'bahan' => [
                    ['nama' => 'Beras putih', 'jumlah' => 300, 'satuan' => 'gram'],
                ],
            ]))
            ->assertRedirect(route('studio.index'));

        $resep = Resep::query()->firstOrFail();

        $this->assertNull($resep->rendemen());
    }

    /**
     * Validasi: satuan_hasil wajib bila hasil_total diisi; hasil_total
     * tidak boleh nol atau negatif.
     */
    public function test_validasi_field_standar(): void
    {
        $chef = User::factory()->create();

        // hasil_total terisi tapi satuan_hasil kosong → ditolak.
        $this->actingAs($chef)
            ->post(route('studio.store'), $this->dataValid([
                'hasil_total' => 900,
                'satuan_hasil' => null,
            ]))
            ->assertSessionHasErrors(['satuan_hasil']);

        // hasil_total nol → ditolak.
        $this->actingAs($chef)
            ->post(route('studio.store'), $this->dataValid([
                'hasil_total' => 0,
                'satuan_hasil' => 'gram',
            ]))
            ->assertSessionHasErrors(['hasil_total']);

        $this->assertSame(0, Resep::query()->count());
    }

    /* ─────────────────────────── Fixture ─────────────────────────── */

    /**
     * Payload lengkap yang lulus validasi — bentuknya mengikuti kolom jsonb.
     *
     * @return array<string, mixed>
     */
    private function dataValid(array $tambahan = []): array
    {
        return array_merge([
            'judul' => 'Sayur Lodeh Terong Muda',
            'deskripsi' => 'Kuah santan gurih dengan terong muda dan kacang panjang untuk makan siang sederhana.',
            'kategori' => 'sayur_nabati',
            'tingkat_kesulitan' => 'mudah',
            'waktu_masak_menit' => 35,
            'porsi' => 4,
            'energi_kkal' => 180.5,
            'protein_g' => 6.2,
            'lemak_g' => 9.1,
            'karbohidrat_g' => 18.4,
            'bahan' => [
                ['nama' => 'Terong muda', 'jumlah' => 4, 'satuan' => 'buah'],
                ['nama' => 'Santan', 'jumlah' => 400, 'satuan' => 'ml'],
            ],
            'langkah' => [
                'Potong terong muda serong kasar lalu rendam sebentar.',
                'Tumis bumbu halus sampai harum, tuang santan, aduk perlahan.',
                'Masukkan terong dan kacang panjang, masak sampai empuk.',
            ],
            'status' => 'draft',
            'gambar' => null,
        ], $tambahan);
    }
}
