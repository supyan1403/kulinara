<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

/**
 * Foto sampul (cover) profil pengguna.
 *
 * Serupa dengan AvatarController: berkas disimpan di public/images/cover/
 * dengan path relatif. Server tidak memasang GD, sehingga pemangkasan
 * dipegang di browser/klien.
 */
class CoverController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'cover' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'cover.required' => 'Pilih foto terlebih dahulu.',
            'cover.image' => 'Berkas harus berupa gambar.',
            'cover.mimes' => 'Format foto harus JPG, PNG, atau WebP.',
            'cover.max' => 'Ukuran foto maksimal 4 MB.',
        ]);

        $user = $request->user();
        $berkas = $request->file('cover');
        $nama = $berkas->hashName();

        File::ensureDirectoryExists(public_path('images/cover'));
        $berkas->move(public_path('images/cover'), $nama);

        $this->hapus($user);
        $user->update(['foto_sampul' => 'images/cover/'.$nama]);

        return back()->with('success', 'Foto sampul diperbarui.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        $this->hapus($user);
        $user->update(['foto_sampul' => null]);

        return back()->with('success', 'Foto sampul dihapus.');
    }

    private function hapus(User $user): void
    {
        $path = $user->foto_sampul;

        if ($path !== null && str_starts_with($path, 'images/cover/')) {
            File::delete(public_path($path));
        }
    }
}
