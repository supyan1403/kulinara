<?php

namespace App\Http\Controllers;

use App\Models\Resep;
use App\Models\Suka;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Inertia\Inertia;
use Inertia\Response;

class ProfilPenggunaController extends Controller
{
    /**
     * Jumlah resep unggulan yang ditampilkan di puncak profil.
     *
     * Sama dengan lebar satu baris grid (4 kolom di layar lebar), supaya
     * bagian unggulan terbaca sebagai satu baris etalase — bukan setengah
     * grid yang menyisakan lubang di ujungnya.
     */
    private const JUMLAH_UNGGULAN = 4;

    /**
     * Halaman profil publik — diakses dari nama penulis di tiap resep.
     *
     * Hanya menampilkan resep yang sudah terbit, mirip grid profil di
     * Instagram. Semua kolom opsional: pengguna yang belum mengisi profil
     * tetap punya halaman dengan nama dan grid resepnya.
     *
     * Bagian "Unggulan" TIDAK lagi membaca kolom `unggulan` di database —
     * kolom itu hanya diisi seeder dan tidak punya antarmuka apa pun.
     * Unggulan kini dihitung dari kinerja: skor tertinggi di antara seluruh
     * resep terbit miliknya (rumus di `Resep::skorUnggulan()`).
     */
    public function show(Request $request, string $username): Response
    {
        $pengguna = User::where('username', $username)->firstOrFail();
        $auth = $request->user();

        // ── Daftar resep yang SUDAH disimpan oleh user yang sedang login ──
        // Dipakai di RecipeCard supaya icon bookmark (fill/outline) mencerminkan
        // keadaan nyata: user menyimpan resep ini atau belum. Tanpa ini, kartu
        // selalu menampilkan bookmark outline — terlihat seperti tombol
        // simpan yang tidak pernah di-klik.
        $resepDisimpan = $auth
            ? $auth->simpanan()
                ->whereHas('resep', fn ($q) => $q->terbit())
                ->pluck('resep_id')
                ->all()
            : [];

        // ── Profil sendiri ──
        // Yang dilihat pemilik akun di profilnya adalah KOLEKSI pribadinya:
        // resep yang ia simpan + resep yang ia sukai. Bukan etalase resep
        // buatan sendiri — etalase itu hidup di Studio Dapur.
        if ($auth && $auth->id === $pengguna->id) {
            // Eloquent\Collection, bukan Support\Collection: `loadCount`
            // hanya ada di yang pertama. Data lahir dari relasi (bukan query
            // resep biasa), jadi jumlah like & simpanan dihitung lewat
            // loadCount — sama persis dengan halaman /simpanan.
            $tersimpan = new EloquentCollection($auth->simpanan()
                ->whereHas('resep', fn ($q) => $q->terbit())
                ->with('resep.penulis:id,name,username,avatar_path')
                ->latest()
                ->get()
                ->map(fn ($s) => $s->resep)
                ->filter()
                ->values()->all());

            $disukai = new EloquentCollection($auth->sukaan()
                ->where('sukaable_type', Suka::RESEP)
                ->whereHas('resep', fn ($q) => $q->terbit())
                ->with('resep.penulis:id,name,username,avatar_path')
                ->latest()
                ->get()
                ->map(fn ($s) => $s->resep)
                ->filter()
                ->values()->all());

            $tersimpan->loadCount(['suka as jumlah_suka', 'disimpanOleh as jumlah_simpanan']);
            $disukai->loadCount(['suka as jumlah_suka', 'disimpanOleh as jumlah_simpanan']);

            $koleksi = $tersimpan->concat($disukai)->unique('id')->values();

            $sukaProfilCount = $pengguna->sukaProfil()->count();

            // Unggulan koleksi: gabungan tersimpan+disukai, diurutkan menurut
            // seberapa banyak resep itu disukai/disimpan ORANG LAIN — skor yang
            // sama dengan `Resep::skorUnggulan()`. Kolom jumlah_ulasan tidak ada
            // di koleksi ini (bukan resep milik sendiri), jadi hanya dua bahan
            // yang bisa dipakai.
            $unggulanKoleksi = $koleksi
                ->sortByDesc(fn ($r) => ((int) $r->jumlah_suka) * 3 + ((int) $r->jumlah_simpanan) * 2)
                ->take(self::JUMLAH_UNGGULAN)
                ->values();

            return Inertia::render('ProfilPengguna', [
                'pengguna' => $pengguna->only([
                    'id', 'name', 'username', 'bio', 'lokasi', 'website', 'avatar_path',
                    'foto_sampul', 'verified',
                ]),
                'mode' => 'koleksi',
                'resepTersimpan' => $tersimpan,
                'resepDisukai' => $disukai,
                'resepDisimpan' => $resepDisimpan,
                'unggulan' => $unggulanKoleksi,
                'koleksi' => $koleksi,
                'rataRating' => null,
                'jumlahResep' => $koleksi->count(),
                'jumlahUlasan' => 0,
                'sukaProfilCount' => $sukaProfilCount,
                'sukaMasakanCount' => 0,
                'sudahSukaProfil' => false,
                'statsKoleksi' => [
                    'disimpan' => $tersimpan->count(),
                    'disukai' => $disukai->count(),
                    'sukaProfil' => $sukaProfilCount,
                ],
            ]);
        }

        // ── Profil orang lain (publik) ──
        $resep = Resep::query()
            ->where('user_id', $pengguna->id)
            ->terbit()
            ->with('penulis:id,name,username,avatar_path')
            ->withCount('disimpanOleh as jumlah_simpanan')
            ->withCount('suka as jumlah_suka')
            ->latest()
            ->get();

        // ── Unggulan otomatis: Top-N berdasarkan skor ──
        // sortByDesc menerima closure, jadi perhitungan skor cukup hidup di
        // satu tempat (Resep::skorUnggulan) tanpa daftar skor kedua.
        //
        // Fallback alami: resep tanpa like sama sekali tetap punya skor
        // rating×ulasan + simpanan×2, jadi bagian ini tidak pernah kosong
        // hanya karena belum ada yang menekan hati.
        $unggulan = $resep
            ->sortByDesc(fn (Resep $r) => $r->skorUnggulan())
            ->take(self::JUMLAH_UNGGULAN)
            ->values();

        // `resep` = SELURUH daftar (bukan sisanya). Tab Terbaru/Disukai/
        // Disimpan mengurutkan daftar lengkap, sehingga angka "(8)" di judul
        // grid selalu cocok dengan statistik "8 resep terbit" di atas —
        // memecahkan resep jadi "4 unggulan + 4 sisanya" membuat jumlah di
        // tab terlihat separuh dari kenyataan. Resep unggulan tetap juga
        // muncul di tab lain (penanda utama mereka adalah tab Unggulan).
        $resep = $resep->values();

        $berrating = $resep->where('jumlah_ulasan', '>', 0);
        $rataRating = $berrating->count() > 0
            ? round((float) $berrating->avg('rata_rating'), 1)
            : null;

        $jumlahResep = $resep->count();
        $jumlahUlasan = (int) $resep->sum('jumlah_ulasan');

        // Statistik like:
        //  - suka profil  : baris `suka` ber-tipe Profil yang menunjuk dia
        //  - suka masakan : jumlah like seluruh resepnya — dijumlah dari
        //    koleksi yang sudah di-withCount tadi, tanpa query ekstra
        $sukaProfilCount = $pengguna->sukaProfil()->count();
        $sukaMasakanCount = (int) $resep->sum('jumlah_suka');

        // Pengunjung: sudah menyukai profil ini atau belum. Tamu = false —
        // hanya menentukan isi hati, bukan tampil tidaknya tombol.
        $sudahSukaProfil = $request->user()
            ? $request->user()->sukaan()
                ->where('sukaable_type', Suka::PROFIL)
                ->where('sukaable_id', $pengguna->id)
                ->exists()
            : false;

        return Inertia::render('ProfilPengguna', [
            'pengguna' => $pengguna->only([
                'id', 'name', 'username', 'bio', 'lokasi', 'website', 'avatar_path',
                'foto_sampul', 'verified',
            ]),
            'mode' => 'publik',
            'resep' => $resep,
            'resepDisimpan' => $resepDisimpan,
            'unggulan' => $unggulan,
            'rataRating' => $rataRating,
            'jumlahResep' => $jumlahResep,
            'jumlahUlasan' => $jumlahUlasan,
            'sukaProfilCount' => $sukaProfilCount,
            'sukaMasakanCount' => $sukaMasakanCount,
            'sudahSukaProfil' => $sudahSukaProfil,
        ]);
    }
}
