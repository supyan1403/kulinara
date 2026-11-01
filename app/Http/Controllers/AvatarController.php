<?php

namespace App\Http\Controllers;

use App\Models\User;
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
 * Berkas disimpan di public/images/avatar/ dengan path relatif, mengikuti
 * aturan yang sama dengan gambar resep.
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
        $berkas = $request->file('avatar');
        $nama = $berkas->hashName();
        $berkas->move(public_path('images/avatar'), $nama);

        $this->hapus($user);
        $user->update(['avatar_path' => 'images/avatar/'.$nama]);

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
     * Hapus berkas foto lama dari disk.
     */
    private function hapus(User $user): void
    {
        $path = $user->avatar_path;

        if ($path !== null && str_starts_with($path, 'images/avatar/')) {
            File::delete(public_path($path));
        }
    }
}
