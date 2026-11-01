<?php

namespace App\Http\Controllers;

use App\Models\Resep;
use App\Models\Suka;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Toggle "suka" — bentuk apresiasi tunggal yang menggantikan fitur follow.
 *
 * Dua jenis target lewat dua endpoint:
 *
 *  - suka profil : anggukan ke si koki; jadi statistik "suka profil" dan
 *                  masuk skor resep unggulannya
 *  - suka resep  : like masakan; bahan utama skor unggulan
 *
 * Keduanya memakai toggle yang sama: baris ada → dihapus (unlike), tidak ada
 * → dibuat. Kunci unik di tabel `suka` menjamin tidak mungkin ada like ganda
 * walaupun dua permintaan dikirim berbarengan — permintaan kedua hanya
 * gagal diam-diam saat membuat, dan hasil akhirnya tetap satu baris.
 */
class SukaController extends Controller
{
    /**
     * Toggle suka profil target.
     *
     * Menyukai profil sendiri ditolak di server, bukan hanya disembunyikan
     * dari tampilan: tombol memang tidak dirender untuk pemilik profil, tapi
     * jalan pintas langsung ke URL tetap harus menemui tembok yang sama.
     */
    public function profil(Request $request, User $pengguna): RedirectResponse
    {
        $pelaku = $request->user();

        if ($pelaku->is($pengguna)) {
            return back()->with('error', 'Tidak bisa menyukai profil sendiri.');
        }

        $ada = $pelaku->sukaan()
            ->where('sukaable_type', Suka::PROFIL)
            ->where('sukaable_id', $pengguna->id)
            ->first();

        if ($ada) {
            $ada->delete();

            return back()->with('success', 'Suka pada @'.$pengguna->username.' dibatalkan.');
        }

        $pelaku->sukaan()->create([
            'sukaable_type' => Suka::PROFIL,
            'sukaable_id' => $pengguna->id,
        ]);

        return back()->with('success', 'Anda menyukai profil @'.$pengguna->username.'.');
    }

    /**
     * Toggle suka resep.
     *
     * Penulis dilarang menyukai resepnya sendiri — alasan yang sama seperti
     * aturan "like sendiri dilarang": angka apresiasi yang bisa diisi sendiri
     * oleh pemiliknya kehilangan makna.
     */
    public function resep(Request $request, string $slug): RedirectResponse
    {
        $pelaku = $request->user();
        $target = Resep::query()->where('slug', $slug)->firstOrFail();

        if ($target->user_id === $pelaku->id) {
            return back()->with('error', 'Tidak bisa menyukai resep sendiri.');
        }

        $ada = $pelaku->sukaan()
            ->where('sukaable_type', Suka::RESEP)
            ->where('sukaable_id', $target->id)
            ->first();

        if ($ada) {
            $ada->delete();

            return back()->with('success', 'Like dibatalkan.');
        }

        $pelaku->sukaan()->create([
            'sukaable_type' => Suka::RESEP,
            'sukaable_id' => $target->id,
        ]);

        return back()->with('success', 'Anda menyukai "'.$target->judul.'".');
    }
}
