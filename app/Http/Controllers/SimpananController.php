<?php

namespace App\Http\Controllers;

use App\Enums\KategoriResep;
use App\Models\Resep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class SimpananController extends Controller
{
    /**
     * Daftar resep yang disimpan pengguna yang sedang login.
     *
     * `pluck('resep')` membalik bentuknya: dari koleksi baris `simpanan`
     * menjadi koleksi `resep` siap tampil, sehingga halaman React cukup
     * memetakan hasilnya lewat RecipeCard yang sudah ada tanpa tahu apa-apa
     * tentang tabel perantara.
     *
     * Dipaginasi 12 per halaman — tanpa ini koleksi yang sudah puluhan
     * resep membuat halaman terlalu panjang ke bawah untuk dipindai.
     */
    public function index(Request $request): Response
    {
        $halaman = $request->user()
            ->simpanan()
            // Saringan scope `terbit()`: resep yang turun ke Draft tidak
            // pernah tampil — halaman detailnya memakai scope yang sama,
            // jadi kartu di sini pasti berujung 404. Angka "Resep
            // tersimpan" di /dashboard memakai saringan identik supaya
            // keduanya tidak mungkin berbeda.
            ->whereHas('resep', fn ($q) => $q->terbit())
            ->with('resep.penulis:id,name,username,avatar_path')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        // Koleksinya lahir dari relasi simpanan, jadi jumlah like & simpanan
        // dihitung lewat loadCount (satu query) — hasilnya sama dengan withCount pada
        // query resep biasa. Dibungkus Eloquent Collection secara eksplisit:
        // map() biasa bisa menghasilkan Collection dasar yang tidak punya
        // loadCount (error 500 sama seperti di dashboard).
        $resep = new \Illuminate\Database\Eloquent\Collection(
            $halaman->getCollection()
                ->map(fn ($simpanan) => $simpanan->resep)
                ->filter()
                ->values()
                ->all()
        );
        $resep->loadCount(['suka as jumlah_suka', 'disimpanOleh as jumlah_simpanan']);

        return Inertia::render('Simpanan/Index', [
            'kategoriTersedia' => KategoriResep::untukProps(),
            'resep' => $resep,
            // ID resep yang disimpan pengguna yang login — dipakai RecipeCard
            // untuk mengisi icon bookmark per resep, supaya tampilan di sini
            // konsisten dengan halaman profil: resep tersimpan = bookmark
            // penuh, resep lain = outline.
            'resepDisimpan' => $request->user()->simpanan()->pluck('resep_id')->all(),
            // Info paginasi untuk navigasi bawah — `linkCollection()` memberi
            // daftar {url, label, active} siap pakai di React, sama seperti
            // yang dipakai halaman /cari.
            'paginasi' => [
                'halaman_ini' => $halaman->currentPage(),
                'terakhir' => $halaman->lastPage(),
                'total' => $halaman->total(),
                'tautan' => $halaman->linkCollection(),
            ],
        ]);
    }

    /**
     * Balik keadaan simpanan sebuah resep: tandai bila belum, batalkan bila
     * sudah.
     *
     * Sengaja TIDAK memakai flash message. Halaman detail memakai
     * `SiteHeader`, bukan `AuthenticatedLayout`, jadi komponen Toast tidak
     * terpasang di sana — dan memang tidak diperlukan: begitu halaman dimuat
     * ulang, tombolnya sendiri sudah berubah isi dan label, dan itu umpan
     * balik yang memang diharapkan dari tombol penanda.
     *
     * Scope `terbit()` dipasang di sini dengan sengaja. Rute ini menerima
     * ID apa pun, jadi tanpa saringan itu seseorang bisa menyimpan resep
     * Draft — sesuatu yang bahkan tidak bisa ia buka kembali, karena halaman
     * detailnya sendiri berujung 404. Keadaan itu hanya membingungkan.
     */
    public function toggle(Request $request, int $resep)
    {
        $target = Resep::query()->terbit()->findOrFail($resep);
        $pengguna = $request->user();

        $tersimpan = $pengguna->simpanan()->where('resep_id', $target->id)->exists();

        if ($tersimpan) {
            $pengguna->simpanan()->where('resep_id', $target->id)->delete();
        } else {
            // firstOrCreate, bukan create: menekan klik ganda yang datang
            // dalam rentang milidetik sebelum state tombol sempat diperbarui.
            // Unique index ganda tetap menjadi penjaga integritas terakhir.
            $pengguna->simpanan()->firstOrCreate(['resep_id' => $target->id]);
        }

        return Redirect::back();
    }
}
