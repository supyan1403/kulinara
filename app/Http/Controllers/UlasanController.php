<?php

namespace App\Http\Controllers;

use App\Models\Resep;
use App\Models\Ulasan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;

/**
 * Menulis, mengubah, membalas, dan menghapus ulasan.
 *
 * Semua aksi memakai `Redirect::back()`, bukan mengembalikan data. Halaman
 * detail resep dirender Inertia, jadi mengembalikan ke halaman yang sama
 * membuat seluruh daftar ulasan ikut dimuat ulang — dan itu memang yang
 * diinginkan: setelah menulis ulasan, rata-rata bintang di kepala halaman
 * harus ikut berubah, bukan hanya daftarnya.
 *
 * Batas panjang komentar dan balasan dijaga di validasi, bukan di basis data.
 * Kolomnya `text` supaya tidak perlu migrasi kalau batasnya berubah; yang
 * penting adalah apa yang diterima pengguna, dan itu ditentukan di sini.
 */
class UlasanController extends Controller
{
    /**
     * Panjang maksimum isi ulasan & balasan.
     */
    private const MAKS_KARAKTER = 2000;

    /**
     * Tulis ulasan baru untuk sebuah resep.
     *
     * Rating wajib; komentar opsional supaya orang yang hanya ingin menekan
     * bintang tidak dipaksa menulis kalimat.
     */
    public function store(Request $request, int $resep)
    {
        $target = Resep::query()->terbit()->with('penulis:id,name')->findOrFail($resep);
        $pengguna = $request->user();

        Gate::forUser($pengguna)->authorize('create', [Ulasan::class, $target->id, $target->user_id]);

        $data = $this->validasi($request);

        // `firstOrCreate` dengan nilai rating: menekan tombol kirim dua kali
        // dalam rentang milidetik sebelum halaman sempat dimuat ulang tidak
        // menghasilkan dua baris — percobaan kedua menemukan baris yang sudah
        // ada dan memperbaruinya, bukan gagal dengan galat basis data.
        $ulasan = Ulasan::query()->firstOrNew([
            'resep_id' => $target->id,
            'user_id' => $pengguna->id,
        ]);

        $ulasan->rating = $data['rating'];
        $ulasan->komentar = $data['komentar'] ?? null;
        $ulasan->save();

        return Redirect::back();
    }

    /**
     * Ubah ulasan yang sudah ada — hanya penulisnya.
     */
    public function update(Request $request, Ulasan $ulasan)
    {
        Gate::authorize('update', $ulasan);

        $data = $this->validasi($request);

        $ulasan->update([
            'rating' => $data['rating'],
            'komentar' => $data['komentar'] ?? null,
        ]);

        return Redirect::back();
    }

    /**
     * Hapus ulasan — penulisnya sendiri, atau Pengelola untuk moderasi.
     *
     * Menghapus ulasan tidak menghapus balasannya secara terpisah: balasan
     * hidup di baris yang sama, jadi ikut hilang. Itu memang yang diinginkan —
     * balasan atas ulasan yang sudah tidak ada tidak punya tempat berpijak.
     */
    public function destroy(Ulasan $ulasan)
    {
        Gate::authorize('delete', $ulasan);

        $ulasan->delete();

        return Redirect::back();
    }

    /**
     * Balas ulasan — hanya pemilik resep yang diulas.
     *
     * Mengirim balasan kosong berarti menghapus balasan yang ada, sehingga
     * pemilik resep bisa membatalkan balasannya tanpa tombol terpisah.
     */
    public function balas(Request $request, Ulasan $ulasan)
    {
        Gate::authorize('balas', $ulasan);

        $data = $request->validate([
            'balasan' => ['nullable', 'string', 'max:'.self::MAKS_KARAKTER],
        ], $this->pesan());

        $isi = trim((string) ($data['balasan'] ?? ''));

        $ulasan->update([
            'balasan' => $isi === '' ? null : $isi,
            'balasan_at' => $isi === '' ? null : now(),
        ]);

        return Redirect::back();
    }

    /**
     * Aturan validasi yang sama untuk tulis dan ubah.
     *
     * @return array<string, mixed>
     */
    private function validasi(Request $request): array
    {
        return $request->validate([
            'rating' => [
                'required',
                'integer',
                'between:'.Ulasan::RATING_MIN.','.Ulasan::RATING_MAKS,
            ],
            'komentar' => ['nullable', 'string', 'max:'.self::MAKS_KARAKTER],
        ], $this->pesan());
    }

    /**
     * Pesan galat berbahasa Indonesia.
     *
     * Tanpa ini, pengguna melihat "The rating field is required." — kalimat
     * Inggris di tengah antarmuka berbahasa Indonesia. Proyek ini belum
     * memuat berkas terjemahan, jadi pesannya ditulis di sini; kalau kelak
     * terjemahan resmi ditambahkan, bagian ini bisa dihapus.
     *
     * @return array<string, string>
     */
    private function pesan(): array
    {
        return [
            'rating.required' => 'Pilih jumlah bintang sebelum mengirim ulasan.',
            'rating.between' => 'Bintang harus antara '.
                Ulasan::RATING_MIN.' sampai '.Ulasan::RATING_MAKS.'.',
            'rating.integer' => 'Bintang harus berupa angka.',
            'komentar.max' => 'Ulasan terlalu panjang (maksimum '.
                self::MAKS_KARAKTER.' karakter).',
            'balasan.max' => 'Balasan terlalu panjang (maksimum '.
                self::MAKS_KARAKTER.' karakter).',
        ];
    }
}
