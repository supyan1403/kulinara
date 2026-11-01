<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Jalur keluar akun dari halaman publik.
 *
 * Halaman publik (/resep, halaman detail, beranda) memakai SiteHeader, bukan
 * AuthenticatedLayout — dan AuthenticatedLayout adalah satu-satunya tempat
 * yang dulu punya menu logout. Akibatnya pengguna yang sudah masuk tidak
 * punya cara keluar dari sana: tidak ada tombol, tidak ada menu, tidak ada
 * tautan. Ia harus mengetik /logout sendiri, sesuatu yang tidak diketahui
 * siapa pun. Satu-satunya tanda bahwa ia masih masuk adalah tautan
 * "Simpanan" yang muncul padahal tombol "Masuk" lenyap.
 *
 * SiteHeader kini punya menu akun (nama → Profile / Log Out). Test di bawah
 * menjaga agar hal-hal yang menu itu bergantung padanya tetap ada: data
 * pengguna benar-benar sampai ke header, rutenya tidak hilang, dan logout
 * benar-benar bekerja.
 */
class JalurKeluarAkunTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_publik_meneruskan_data_pengguna_ke_header(): void
    {
        $pengguna = User::factory()->create();

        // Tanpa prop `auth.user`, SiteHeader tidak bisa tahu siapa yang
        // masuk — menu akun beserta tombol logout-nya tidak akan pernah
        // muncul, dan keadaan sebelumnya kembali tanpa suara.
        $this->actingAs($pengguna)
            ->get(route('resep.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.id', $pengguna->id)
                ->where('auth.user.name', $pengguna->name)
            );
    }

    public function test_rute_yang_dibutuhkan_menu_akun_tetap_ada(): void
    {
        $this->assertTrue(
            Route::has('logout'),
            'Rute logout hilang — menu akun di SiteHeader akan menampilkan tautan yang mengarah ke rute tak bernama.',
        );
        $this->assertTrue(
            Route::has('login'),
            'Rute login hilang — pengguna yang keluar tidak punya jalan masuk kembali dari halaman publik.',
        );
    }

    public function test_pengguna_yang_masuk_bisa_keluar(): void
    {
        $pengguna = User::factory()->create();

        $this->actingAs($pengguna)->post(route('logout'));

        $this->assertGuest();
    }
}
