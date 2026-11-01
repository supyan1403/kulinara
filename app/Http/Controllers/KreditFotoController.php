<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Inertia\Inertia;

class KreditFotoController extends Controller
{
    /**
     * Halaman kredit foto.
     *
     * Hampir seluruh foto hidangan diambil dari Wikimedia Commons dengan
     * lisensi Creative Commons Attribution-ShareAlike. Syarat lisensinya
     * mewajibkan mencantumkan nama pembuat, tautan ke sumber, dan keterangan
     * bahwa gambar mengalami perubahan. Halaman inilah tempat kredit itu ada.
     *
     * Daftarnya dibaca dari manifest yang ditulis scripts/unduh-foto.mjs,
     * bukan disusun tangan — jadi kredit selalu mengikuti apa yang benar-benar
     * terunduh, dan tidak pernah bisa tertinggal saat dataset ditambah.
     */
    public function index()
    {
        $berkas = database_path('seeders/data/foto.json');

        $foto = collect(is_file($berkas) ? File::json($berkas) : []);

        $daftar = $foto
            ->map(fn (array $f) => [
                'judul' => $f['judul'],
                'penulis' => $f['penulis'] ?? 'Penulis tidak tercatat',
                'lisensi' => $f['lisensi'] ?? 'Lisensi tidak tercatat',
                'halaman' => $f['halaman'],
            ])
            ->sortBy('judul', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return Inertia::render('KreditFoto', [
            'jumlahFoto' => $daftar->count(),
            'jumlahLisensi' => $daftar
                ->countBy(fn (array $f) => $f['lisensi'])
                ->map(fn (int $jumlah, string $nama) => ['nama' => $nama, 'jumlah' => $jumlah])
                ->sortByDesc('jumlah')
                ->values(),
            'foto' => $daftar,
        ]);
    }
}
