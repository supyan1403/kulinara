<?php

namespace Tests\Feature;

use App\Enums\StatusResep;
use App\Models\Resep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alur kurasi resep: draft → diajukan → ditinjau Pengelola.
 *
 * Yang dijaga di sini bukan sekadar status, tapi dua janji produk:
 *
 *   1. Kreator tidak bisa menayangkan resepnya sendiri — tanpa ini, kurasi
 *      hanya hiasan.
 *   2. Penolakan selalu beralasan — penulis yang tidak tahu apa yang salah
 *      akan berhenti menulis.
 */
class KurasiResepTest extends TestCase
{
    use RefreshDatabase;

    /* ─────────────────── Kreator tidak bisa menembus ─────────────────── */

    public function test_kreator_tidak_bisa_menayangkan_resep_sendiri(): void
    {
        $chef = User::factory()->create();

        $this->actingAs($chef)
            ->post(route('studio.store'), $this->dataValid(['status' => 'terbit']))
            ->assertSessionHasErrors('status');

        $this->assertSame(0, Resep::query()->count());
    }

    public function test_kreator_tidak_bisa_menaikkan_status_lewat_formulir_sunting(): void
    {
        $chef = User::factory()->create();
        $resep = Resep::factory()->for($chef, 'penulis')->create([
            'status' => StatusResep::Draft,
        ]);

        $this->actingAs($chef)
            ->put(route('studio.update', $resep), $this->dataValid(['status' => 'terbit']))
            ->assertSessionHasErrors('status');

        $this->assertSame(StatusResep::Draft, $resep->fresh()->status);
    }

    /* ─────────────────── Ajukan & tarik ─────────────────── */

    public function test_kreator_bisa_mengajukan_draft_miliknya(): void
    {
        $chef = User::factory()->create();
        $resep = Resep::factory()->for($chef, 'penulis')->create([
            'status' => StatusResep::Draft,
        ]);

        $this->actingAs($chef)
            ->post(route('studio.ajukan-resep', $resep))
            ->assertRedirect();

        $resep->refresh();

        $this->assertSame(StatusResep::Diajukan, $resep->status);
        $this->assertNotNull($resep->diajukan_at);
    }

    public function test_kreator_tidak_bisa_mengajukan_resep_orang_lain(): void
    {
        $chef = User::factory()->create();
        $resep = Resep::factory()->for(
            User::factory()->create(),
            'penulis',
        )->create(['status' => StatusResep::Draft]);

        $this->actingAs($chef)
            ->post(route('studio.ajukan-resep', $resep))
            ->assertStatus(403);

        $this->assertSame(StatusResep::Draft, $resep->fresh()->status);
    }

    public function test_resep_yang_sudah_diajukan_tidak_bisa_diajukan_dua_kali(): void
    {
        $chef = User::factory()->create();
        $resep = Resep::factory()->for($chef, 'penulis')->create([
            'status' => StatusResep::Diajukan,
        ]);

        $this->actingAs($chef)
            ->post(route('studio.ajukan-resep', $resep))
            ->assertSessionHas('error');

        $this->assertSame(StatusResep::Diajukan, $resep->fresh()->status);
    }

    public function test_kreator_bisa_menarik_pengajuan_yang_belum_ditinjau(): void
    {
        $chef = User::factory()->create();
        $resep = Resep::factory()->for($chef, 'penulis')->create([
            'status' => StatusResep::Diajukan,
            'diajukan_at' => now(),
        ]);

        $this->actingAs($chef)
            ->post(route('studio.tarik-resep', $resep))
            ->assertRedirect();

        $resep->refresh();

        $this->assertSame(StatusResep::Draft, $resep->status);
        $this->assertNull($resep->diajukan_at);
    }

    /* ─────────────────── Keputusan Pengelola ─────────────────── */

    public function test_pengelola_melihat_antrean_dari_yang_paling_lama(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $chef = User::factory()->create();

        Resep::factory()->for($chef, 'penulis')->create([
            'judul' => 'Diajukan Belakangan',
            'status' => StatusResep::Diajukan,
            'diajukan_at' => now()->subHour(),
        ]);
        Resep::factory()->for($chef, 'penulis')->create([
            'judul' => 'Diajukan Duluan',
            'status' => StatusResep::Diajukan,
            'diajukan_at' => now()->subDay(),
        ]);
        // Draft dan Terbit tidak boleh ikut mengotori antrean.
        Resep::factory()->for($chef, 'penulis')->create(['status' => StatusResep::Draft]);
        Resep::factory()->for($chef, 'penulis')->create(['status' => StatusResep::Terbit]);

        $this->actingAs($pengelola)
            ->get(route('kurasi.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Panel/Kurasi')
                ->has('antrean', 2)
                ->where('antrean.0.judul', 'Diajukan Duluan')
                ->where('antrean.1.judul', 'Diajukan Belakangan')
                ->where('statistik.menunggu', 2));
    }

    public function test_pengelola_menyetujui_pengajuan_dan_resep_tayang(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $resep = Resep::factory()->for(
            User::factory()->create(),
            'penulis',
        )->create([
            'status' => StatusResep::Diajukan,
            'diajukan_at' => now(),
            'catatan_kurasi' => 'Catatan lama yang harus bersih.',
        ]);

        $this->actingAs($pengelola)
            ->post(route('kurasi.setujui', $resep))
            ->assertRedirect();

        $resep->refresh();

        $this->assertSame(StatusResep::Terbit, $resep->status);
        $this->assertSame($pengelola->id, $resep->ditinjau_oleh);
        $this->assertNotNull($resep->ditinjau_at);
        $this->assertNull($resep->catatan_kurasi);
    }

    public function test_penolakan_tanpa_catatan_ditolak(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $resep = Resep::factory()->for(
            User::factory()->create(),
            'penulis',
        )->create([
            'status' => StatusResep::Diajukan,
            'diajukan_at' => now(),
        ]);

        $this->actingAs($pengelola)
            ->post(route('kurasi.tolak', $resep), ['catatan' => ''])
            ->assertSessionHasErrors('catatan');

        $this->assertSame(StatusResep::Diajukan, $resep->fresh()->status);
    }

    public function test_penolakan_bercatatan_mengembalikan_resep_ke_draft(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $resep = Resep::factory()->for(
            User::factory()->create(),
            'penulis',
        )->create([
            'status' => StatusResep::Diajukan,
            'diajukan_at' => now(),
        ]);

        $this->actingAs($pengelola)
            ->post(route('kurasi.tolak', $resep), [
                'catatan' => 'Langkah 2 belum jelas; tulis api besar atau kecil.',
            ])
            ->assertRedirect();

        $resep->refresh();

        $this->assertSame(StatusResep::Draft, $resep->status);
        $this->assertStringContainsString('Langkah 2', $resep->catatan_kurasi);
        $this->assertSame($pengelola->id, $resep->ditinjau_oleh);
    }

    /* ─────────────────── Batas peran ─────────────────── */

    public function test_kreator_tidak_bisa_membuka_panel_kurasi(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('kurasi.index'))
            ->assertStatus(403);
    }

    public function test_kreator_tidak_bisa_menyetujui_resep_sendiri(): void
    {
        $chef = User::factory()->create();
        $resep = Resep::factory()->for($chef, 'penulis')->create([
            'status' => StatusResep::Diajukan,
            'diajukan_at' => now(),
        ]);

        $this->actingAs($chef)
            ->post(route('kurasi.setujui', $resep))
            ->assertStatus(403);

        $this->assertSame(StatusResep::Diajukan, $resep->fresh()->status);
    }

    /* ─────────────────── Pengawasan suntingan ─────────────────── */

    public function test_menyunting_resep_tayang_ditandai_untuk_diperiksa(): void
    {
        $chef = User::factory()->create();
        $resep = Resep::factory()->for($chef, 'penulis')->create([
            'status' => StatusResep::Terbit,
        ]);

        $this->assertNull($resep->disunting_setelah_terbit_at);

        $this->actingAs($chef)
            ->put(route('studio.update', $resep), $this->dataValid([
                'judul' => 'Judul Yang Diperbaiki',
            ]))
            ->assertRedirect(route('studio.index'));

        $resep->refresh();

        $this->assertSame('Judul Yang Diperbaiki', $resep->judul);
        $this->assertSame(StatusResep::Terbit, $resep->status);
        $this->assertNotNull($resep->disunting_setelah_terbit_at);
    }

    public function test_menyunting_draft_tidak_menandai_pengawasan(): void
    {
        $chef = User::factory()->create();
        $resep = Resep::factory()->for($chef, 'penulis')->create([
            'status' => StatusResep::Draft,
        ]);

        $this->actingAs($chef)
            ->put(route('studio.update', $resep), $this->dataValid())
            ->assertRedirect(route('studio.index'));

        $this->assertNull($resep->fresh()->disunting_setelah_terbit_at);
    }

    public function test_pengelola_menandai_suntingan_sudah_diperiksa(): void
    {
        $pengelola = User::factory()->pengelola()->create();
        $resep = Resep::factory()->for(
            User::factory()->create(),
            'penulis',
        )->create([
            'status' => StatusResep::Terbit,
            'disunting_setelah_terbit_at' => now(),
        ]);

        $this->actingAs($pengelola)
            ->post(route('kurasi.diperiksa', $resep))
            ->assertRedirect();

        $resep->refresh();

        $this->assertNull($resep->disunting_setelah_terbit_at);
        $this->assertSame(StatusResep::Terbit, $resep->status);
        $this->assertSame($pengelola->id, $resep->ditinjau_oleh);
    }

    /**
     * Payload formulir yang sah — disalin dari StudioRecipeCrudTest agar
     * validasi diuji dengan bentuk masukan yang realistis.
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
