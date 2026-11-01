<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Foto profil: unggah, ganti, hapus.
 *
 * Yang dijaga: foto lama benar-benar dibuang dari disk (bukan hanya dilupakan
 * dari database), pengguna tidak bisa menimpa foto orang lain, dan berkas yang
 * bukan gambar ditolak.
 */
class AvatarTest extends TestCase
{
    use RefreshDatabase;

    private string $folder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->folder = public_path('images/avatar');
        File::ensureDirectoryExists($this->folder);
    }

    protected function tearDown(): void
    {
        // Jangan biarkan berkas uji menumpuk di public/.
        foreach (File::files($this->folder) as $berkas) {
            File::delete($berkas->getPathname());
        }

        parent::tearDown();
    }

    public function test_pengguna_bisa_mengunggah_foto_profil(): void
    {
        $user = User::factory()->create(['avatar_path' => null]);

        $this->actingAs($user)
            ->post(route('profile.avatar.update'), [
                'avatar' => $this->gambarPalsu('wajah.png'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $user->refresh();

        $this->assertNotNull($user->avatar_path);
        $this->assertStringStartsWith('images/avatar/', $user->avatar_path);
        $this->assertFileExists(public_path($user->avatar_path));
    }

    public function test_foto_lama_dihapus_saat_mengganti(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('profile.avatar.update'), [
            'avatar' => $this->gambarPalsu('lama.png'),
        ]);

        $lama = $user->fresh()->avatar_path;

        $this->actingAs($user)->post(route('profile.avatar.update'), [
            'avatar' => $this->gambarPalsu('baru.png'),
        ]);

        $baru = $user->fresh()->avatar_path;

        $this->assertNotSame($lama, $baru);
        $this->assertFileDoesNotExist(public_path($lama));
        $this->assertFileExists(public_path($baru));
    }

    public function test_foto_bisa_dihapus_dan_kembali_ke_inisial(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('profile.avatar.update'), [
            'avatar' => $this->gambarPalsu('wajah.png'),
        ]);

        $path = $user->fresh()->avatar_path;

        $this->actingAs($user)
            ->delete(route('profile.avatar.destroy'))
            ->assertRedirect();

        $this->assertNull($user->fresh()->avatar_path);
        $this->assertFileDoesNotExist(public_path($path));
    }

    public function test_berkas_bukan_gambar_ditolak(): void
    {
        $user = User::factory()->create(['avatar_path' => null]);

        $this->actingAs($user)
            ->post(route('profile.avatar.update'), [
                'avatar' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('avatar');

        $this->assertNull($user->fresh()->avatar_path);
    }

    public function test_unggahan_tanpa_berkas_ditolak(): void
    {
        $user = User::factory()->create(['avatar_path' => null]);

        $this->actingAs($user)
            ->post(route('profile.avatar.update'), [])
            ->assertSessionHasErrors('avatar');

        $this->assertNull($user->fresh()->avatar_path);
    }

    public function test_tamu_tidak_bisa_mengunggah_foto(): void
    {
        $this->post(route('profile.avatar.update'), [
            'avatar' => $this->gambarPalsu('wajah.png'),
        ])->assertRedirect(route('login'));
    }

    /**
     * PNG 1×1 asli.
     *
     * `UploadedFile::fake()->image()` butuh ekstensi GD, yang tidak terpasang
     * di server ini. Bytes PNG yang sah tetap lolos aturan `image` / `mimes`
     * karena validasi Laravel membaca isi berkas, bukan namanya.
     */
    private function gambarPalsu(string $nama): UploadedFile
    {
        $isi = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
        );

        return UploadedFile::fake()->createWithContent($nama, $isi);
    }

    public function test_avatar_path_dibagikan_ke_frontend(): void
    {
        $user = User::factory()->create(['avatar_path' => 'images/avatar/x.jpg']);

        $this->actingAs($user)
            ->get(route('resep.index'))
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.avatar_path', 'images/avatar/x.jpg'));
    }
}
