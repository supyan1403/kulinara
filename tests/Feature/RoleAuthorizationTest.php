<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_ditolak_ke_studio_dapur(): void
    {
        $this->get(route('studio.index'))->assertRedirect(route('login'));
    }

    public function test_pengguna_bisa_akses_studio_dapur(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('studio.index'))
            ->assertOk();
    }

    public function test_pengelola_bisa_akses_studio_dapur(): void
    {
        $user = User::factory()->pengelola()->create();

        $this->actingAs($user)
            ->get(route('studio.index'))
            ->assertOk();
    }

    public function test_pengguna_ditolak_ke_panel_pengelola(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('panel.users'))
            ->assertForbidden();
    }

    public function test_pengelola_bisa_akses_panel(): void
    {
        $user = User::factory()->pengelola()->create();

        $this->actingAs($user)
            ->get(route('panel.users'))
            ->assertOk();
    }

    public function test_pengelola_bisa_mengubah_role_user_lain(): void
    {
        $admin = User::factory()->pengelola()->create();
        $target = User::factory()->create(['role' => UserRole::Pengguna]);

        $this->actingAs($admin)
            ->patch(route('panel.users.role', $target), ['role' => UserRole::Pengguna->value])
            ->assertSessionHasNoErrors();

        $this->assertSame(UserRole::Pengguna, $target->fresh()->role);
    }

    public function test_pengelola_tidak_bisa_mengubah_role_sendiri(): void
    {
        $admin = User::factory()->pengelola()->create();

        $this->actingAs($admin)
            ->patch(route('panel.users.role', $admin), ['role' => UserRole::Pengguna->value])
            ->assertSessionHasErrors('role');

        $this->assertSame(UserRole::Pengelola, $admin->fresh()->role);
    }

    public function test_role_tidak_valid_ditolak(): void
    {
        $admin = User::factory()->pengelola()->create();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('panel.users.role', $target), ['role' => 'superadmin'])
            ->assertSessionHasErrors('role');
    }

    public function test_pengguna_tidak_bisa_mengubah_role(): void
    {
        $pengguna = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($pengguna)
            ->patch(route('panel.users.role', $target), ['role' => UserRole::Pengelola->value])
            ->assertForbidden();
    }

    public function test_pendaftaran_selalu_menghasilkan_pengguna(): void
    {
        $this->post(route('register'), [
            'name' => 'Pendaftar Baru',
            'email' => 'pendaftar@kulinara.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            // Field role dikirim untuk membuktikan server mengabaikannya.
            'role' => UserRole::Pengelola->value,
        ])->assertRedirect(route('dashboard'));

        $this->assertSame(
            UserRole::Pengguna,
            User::whereEmail('pendaftar@kulinara.test')->first()->role
        );
    }

    public function test_pendaftaran_tidak_bisa_memilih_pengelola(): void
    {
        $this->post(route('register'), [
            'name' => 'Penyusup',
            'email' => 'penyusup@kulinara.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role' => UserRole::Pengelola->value,
        ])->assertRedirect(route('dashboard'));

        // Field role diabaikan, bukan divalidasi — Pengelola tidak pernah
        // tercipta dari pendaftaran.
        $this->assertSame(
            UserRole::Pengguna,
            User::whereEmail('penyusup@kulinara.test')->first()->role
        );
    }
}
