<?php

namespace App\Http\Controllers;

use App\Enums\StatusResep;
use App\Http\Requests\TolakResepRequest;
use App\Models\Resep;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Meja kerja kurasi — hanya untuk Pengelola.
 *
 * Alurnya mengikuti sistem submit artikel: Kreator menulis draft, menekan
 * "Ajukan", lalu resepnya masuk antrean di sini. Keputusan hanya ada dua —
 * setujui (tayang) atau tolak (kembali ke draft dengan alasan). Pengelola
 * sengaja TIDAK menyunting isi resep: takaran menyangkut hasil masakan orang,
 * dan mengubahnya diam-diam membuat penulis kehilangan jejak karyanya.
 */
class KurasiController extends Controller
{
    /**
     * Antrean pengajuan yang menunggu keputusan.
     *
     * Diurutkan dari yang paling lama menunggu, supaya tidak ada pengajuan
     * yang tertimbun di bawah kiriman baru.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Panel/Kurasi', [
            'antrean' => Resep::query()
                ->antreanKurasi()
                ->with(['penulis:id,name,email', 'peninjau:id,name'])
                ->get()
                ->map(fn (Resep $resep) => $this->ringkas($resep))
                ->values(),

            // Resep tayang yang disunting setelah lolos kurasi. Kreator tidak
            // dihukum karena memperbaiki salah ketik, tapi Pengelola perlu
            // melihat apa saja yang berubah agar kurasi tidak bisa diakali
            // (ajukan versi bersih, lolos, lalu ganti isinya).
            'disunting' => Resep::query()
                ->disuntingSetelahTerbit()
                ->with(['penulis:id,name,email', 'peninjau:id,name'])
                ->latest('disunting_setelah_terbit_at')
                ->take(20)
                ->get()
                ->map(fn (Resep $resep) => $this->ringkas($resep))
                ->values(),

            'statistik' => [
                'menunggu' => Resep::query()->antreanKurasi()->count(),
                'disunting' => Resep::query()->disuntingSetelahTerbit()->count(),
            ],
        ]);
    }

    /**
     * Setujui pengajuan — resep naik ke Terbit dan muncul di direktori.
     */
    public function setujui(Request $request, Resep $resep): RedirectResponse
    {
        if (! $resep->status->menungguKurasi()) {
            return back()->with('error', 'Resep ini tidak sedang menunggu kurasi.');
        }

        $resep->update([
            'status' => StatusResep::Terbit,
            'ditinjau_at' => now(),
            'ditinjau_oleh' => $request->user()->id,
            // Catatan penolakan sebelumnya tidak boleh nyangkut di resep yang
            // sudah lolos.
            'catatan_kurasi' => null,
            // Setelah ditinjau ulang, penanda "disunting" tidak relevan lagi.
            'disunting_setelah_terbit_at' => null,
        ]);

        return back()->with('success', "Resep \"{$resep->judul}\" disetujui dan tayang.");
    }

    /**
     * Tolak pengajuan — resep kembali ke Draft, alasannya wajib.
     */
    public function tolak(TolakResepRequest $request, Resep $resep): RedirectResponse
    {
        if (! $resep->status->menungguKurasi()) {
            return back()->with('error', 'Resep ini tidak sedang menunggu kurasi.');
        }

        $resep->update([
            'status' => StatusResep::Draft,
            'ditinjau_at' => now(),
            'ditinjau_oleh' => $request->user()->id,
            'catatan_kurasi' => $request->validated('catatan'),
        ]);

        return back()->with('success', "Resep \"{$resep->judul}\" dikembalikan ke draft.");
    }

    /**
     * Penanda "sudah saya periksa" untuk resep yang disunting setelah terbit.
     *
     * Tidak mengubah status apa pun — hanya membersihkan penanda agar daftar
     * "perlu diperiksa" tidak menumpuk hal-hal yang sudah dilihat.
     */
    public function tandaiDiperiksa(Request $request, Resep $resep): RedirectResponse
    {
        $resep->update([
            'disunting_setelah_terbit_at' => null,
            'ditinjau_at' => now(),
            'ditinjau_oleh' => $request->user()->id,
        ]);

        return back()->with('success', 'Resep ditandai sudah diperiksa.');
    }

    /**
     * Bentuk data yang sama untuk kedua daftar di halaman kurasi.
     *
     * @return array<string, mixed>
     */
    private function ringkas(Resep $resep): array
    {
        return [
            'id' => $resep->id,
            'slug' => $resep->slug,
            'judul' => $resep->judul,
            'deskripsi' => $resep->deskripsi,
            'kategori' => $resep->kategori->label(),
            'status' => $resep->status->value,
            'status_label' => $resep->status->label(),
            'waktu_masak_menit' => $resep->waktu_masak_menit,
            'porsi' => $resep->porsi,
            'gambar' => $resep->gambar,
            'bahan' => $resep->bahan ?? [],
            'langkah' => $resep->langkah ?? [],
            'penulis' => $resep->penulis?->name,
            'penulis_email' => $resep->penulis?->email,
            'diajukan_at' => $resep->diajukan_at?->toIso8601String(),
            'ditinjau_at' => $resep->ditinjau_at?->toIso8601String(),
            'peninjau' => $resep->peninjau?->name,
            'catatan_kurasi' => $resep->catatan_kurasi,
            'disunting_setelah_terbit_at' => $resep->disunting_setelah_terbit_at?->toIso8601String(),
        ];
    }
}
