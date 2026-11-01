<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SupabaseStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

/**
 * Foto profil pengguna.
 *
 * Ukuran dipangkas di browser sebelum dikirim (lihat `Profile/Edit.jsx`):
 * server ini tidak memasang ekstensi GD, sehingga memperkecil gambar di sisi
 * PHP akan gagal diam-diam. Server tetap memeriksa jenis dan ukuran berkas —
 * percayai klien hanya untuk kenyamanan, jangan untuk keamanan.
 *
 * Berkas baru disimpan di bucket Supabase Storage (`uploads/avatar/`) dan
 * kolom `avatar_path` berisi URL publik permanen. Nilai lama yang masih
 * relatif (`images/avatar/...`) tetap berlaku — dilayani dari public/.
 */
class AvatarController extends Controller
{
    /**
     * Simpan foto profil baru, ganti yang lama.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'avatar.required' => 'Pilih foto terlebih dahulu.',
            'avatar.image' => 'Berkas harus berupa gambar.',
            'avatar.mimes' => 'Format foto harus JPG, PNG, atau WebP.',
            'avatar.max' => 'Ukuran foto maksimal 2 MB.',
        ]);

        $user = $request->user();
        $url = SupabaseStorage::make()->upload($request->file('avatar'), 'avatar');

        $this->hapus($user);
        $user->update(['avatar_path' => $url]);

        return back()->with('success', 'Foto profil diperbarui.');
    }

    /**
     * Kembali ke avatar inisial.
     *
     * Selalu disediakan bersama unggah: tanpa jalan keluar, satu foto yang
     * disesali menempel pada akun selamanya.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        $this->hapus($user);
        $user->update(['avatar_path' => null]);

        return back()->with('success', 'Foto profil dihapus.');
    }

    /**
     * Hapus berkas foto lama — dari bucket Supabase (unggahan baru)
     * atau dari public/ (unggahan lama yang masih path relatif).
     */
    private function hapus(User $user): void
    {
        $path = $user->avatar_path;

        if ($path === null) {
            return;
        }

        if (str_starts_with($path, 'images/avatar/')) {
            File::delete(public_path($path));
        } else {
            SupabaseStorage::make()->delete($path);
        }
    }
}
