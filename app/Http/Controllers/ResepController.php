<?php

namespace App\Http\Controllers;

use App\Enums\KategoriResep;
use App\Enums\TingkatKesulitan;
use App\Models\Bahan;
use App\Models\Resep;
use App\Models\Suka;
use App\Models\Ulasan;
use App\Services\KalkulasiGizi;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class ResepController extends Controller
{
    /**
     * Jumlah resep per halaman pada direktori — 12 kartu = tiga baris di
     * lebar desktop, dua baris di tablet, satu baris di ponsel.
     */
    private const PER_HALAMAN = 12;

    /**
     * Jumlah kartu pada blok "Resep lain" di kaki halaman detail.
     */
    private const JUMLAH_TERKAIT = 4;

    /**
     * Direktori seluruh resep yang tayang, dengan pagination.
     *
     * Sengaja terbuka tanpa login. Seluruh isinya memang sudah terlihat di
     * beranda (empat kartu pertama), dan resep yang belum layak tampil sudah
     * tersaring sendiri oleh scope `direktori()`. Pagar yang benar untuk
     * konten yang belum terbit adalah status resep, bukan akun pengunjung —
     * selain itu, halaman FAQ sendiri berjanji bahwa direktori terbuka bebas.
     */
    public function index(Request $request)
    {
        $kategori = KategoriResep::tryFrom((string) $request->query('kategori'));
        $kataCari = trim((string) $request->query('q'));
        $urutkan = (string) $request->query('urutkan', 'terbaru');

        // Saringan lanjutan — semuanya opsional dan bisa dipakai bersamaan.
        $kesulitan = TingkatKesulitan::tryFrom((string) $request->query('kesulitan'));
        $maksMenit = (int) $request->query('maks_menit');
        $minRating = (float) $request->query('min_rating');
        $bahan = trim((string) $request->query('bahan'));
        // Rujukan bahan (dropdown "Mengandung bahan") — lebih presisi
        // daripada menyerempet nama bebas di atas.
        $bahanId = (int) $request->query('bahan_id') ?: null;

        $saring = fn () => Resep::query()
            ->direktori($kategori, $kataCari)
            ->kesulitan($kesulitan)
            ->maksMenit($maksMenit > 0 ? $maksMenit : null)
            ->minRating($minRating > 0 ? $minRating : null)
            ->denganBahan($bahan)
            ->bahanId($bahanId);

        $query = $saring()
            ->with('penulis:id,name,username,avatar_path')
            ->withCount('suka as jumlah_suka')
            ->withCount('disimpanOleh as jumlah_simpanan');

        if ($urutkan === 'rating') {
            // Urutan rating tertinggi: didahulukan yang punya nilai rata-rata tinggi,
            // lalu jumlah ulasan terbanyak, kemudian waktu terbaru.
            $query->orderByDesc('rata_rating')
                ->orderByDesc('jumlah_ulasan')
                ->latest();
        } else {
            $urutkan = 'terbaru';
            $query->etalase();
        }

        return Inertia::render('Resep/Index', [
            'kategoriAktif' => $kategori?->value,
            'kategoriTersedia' => KategoriResep::untukProps(),
            'kataCari' => $kataCari,
            'urutkan' => $urutkan,
            'kesulitan' => $kesulitan?->value,
            'kesulitanTersedia' => TingkatKesulitan::untukProps(),
            'maksMenit' => $maksMenit > 0 ? $maksMenit : null,
            'minRating' => $minRating > 0 ? $minRating : null,
            'bahan' => $bahan,
            'bahanId' => $bahanId,
            // Rujukan bahan untuk dropdown saringan "Mengandung bahan".
            'bahanTersedia' => Bahan::query()
                ->aktif()
                ->orderBy('nama')
                ->get(['id', 'nama'])
                ->map(fn (Bahan $b) => ['id' => $b->id, 'nama' => $b->nama])
                ->values(),
            // withQueryString() wajib: tanpa itu `q`, `kategori`, `urutkan`, dan
            // seluruh saringan lanjutan hilang begitu pengunjung membuka halaman 2,
            // dan hasilnya akan berubah total di tengah pencarian.
            'resep' => $query
                ->paginate(self::PER_HALAMAN)
                ->withQueryString(),
        ]);
    }

    /**
     * Halaman satu resep.
     *
     * `firstOrFail()` membuat slug yang tidak dikenal berujung 404, sementara
     * scope `terbit()` membuat resep berstatus Draft ikut 404 untuk pengunjung
     * biasa — tanpa satu pun cabang kondisi di sini.
     */
    public function show(Request $request, string $slug)
    {
        $resep = Resep::query()
            ->terbit()
            ->where('slug', $slug)
            ->with('penulis:id,name,username,avatar_path')
            ->withCount('suka as jumlah_suka')
            ->withCount('disimpanOleh as jumlah_simpanan')
            ->firstOrFail();

        return Inertia::render('Resep/Detail', [
            'resep' => $resep,
            // Label dipetakan di server supaya React tidak perlu menyimpan
            // daftar label enum kedua — sumbernya tetap enum PHP yang sama
            // yang dipakai halaman beranda untuk chip kategori.
            'kategoriLabel' => $resep->kategori->label(),
            'kesulitanLabel' => $resep->tingkat_kesulitan->label(),
            'kategoriTersedia' => KategoriResep::untukProps(),
            'terkait' => $this->terkait($resep),
            'disimpan' => $this->disimpan($request, $resep),
            'ulasan' => $this->ulasan($resep),
            'ulasanSaya' => $this->ulasanSaya($request, $resep),
            // Keadaan dua tombol like — dihitung di server supaya React tidak
            // perlu mengulang query untuk setiap kartu/penulis yang tampil.
            'sudahSukaResep' => $this->sudahSuka($request, Suka::RESEP, $resep->id),
            'sudahSukaProfil' => $this->sudahSuka($request, Suka::PROFIL, $resep->user_id),
            // Apakah pengunjung berhak membalas — hanya penulis resepnya.
            // Dihitung di server supaya React tidak perlu tahu ID pemilik,
            // dan supaya kotak balasan tidak pernah muncul lalu ditolak.
            'bolehBalas' => $request->user()?->id === $resep->user_id,
            // Data standarisasi — badge "Standar", rendemen, dan total
            // bahan masukan (dipakai kalkulator skala produksi di klien).
            'standar' => $this->standar($resep),
        ]);
    }

    /**
     * Ringkasan standarisasi resep untuk halaman detail.
     *
     * @return array{terpenuhi: bool, rendemen: float|null, hasilTotal: float|null, satuanHasil: string|null, totalGramBahan: float, giziCakupan: int, giziMode: string}
     */
    private function standar(Resep $resep): array
    {
        $totalGramBahan = app(KalkulasiGizi::class)->totalGram($resep->bahan);

        return [
            'terpenuhi' => $resep->standarTerpenuhi(),
            'rendemen' => $resep->rendemen(),
            'hasilTotal' => $resep->hasil_total !== null ? (float) $resep->hasil_total : null,
            'satuanHasil' => $resep->satuan_hasil?->value,
            'totalGramBahan' => $totalGramBahan,
            'giziCakupan' => (int) $resep->gizi_cakupan,
            'giziMode' => (string) $resep->gizi_mode,
        ];
    }

    /**
     * Daftar ulasan resep ini, terbaru lebih dulu.
     *
     * Nama pengulas diambil lewat `with('pengguna:id,name')` supaya daftar
     * sepanjang apa pun tetap satu query — tanpa itu setiap baris memicu
     * query sendiri untuk mengambil penulisnya.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function ulasan(Resep $resep): Collection
    {
        return $resep->ulasan()
            ->with('pengguna:id,name,avatar_path')
            ->latest()
            ->get()
            ->map(fn (Ulasan $u) => [
                'id' => $u->id,
                'rating' => $u->rating,
                'komentar' => $u->komentar,
                'balasan' => $u->balasan,
                'balasan_at' => $u->balasan_at?->diffForHumans(),
                'penulis' => $u->pengguna?->name ?? 'Pengguna terhapus',
                'avatar_path' => $u->pengguna?->avatar_path,
                'user_id' => $u->user_id,
                'dibuat' => $u->created_at?->diffForHumans(),
            ]);
    }

    /**
     * Ulasan milik pengunjung yang sedang login, kalau ada.
     *
     * Dipakai antarmuka untuk memutuskan antara menampilkan formulir tulis
     * atau formulir ubah. Tamu selalu null, dan itu berarti "belum menulis".
     *
     * @return array<string, mixed>|null
     */
    private function ulasanSaya(Request $request, Resep $resep): ?array
    {
        $pengguna = $request->user();

        if (! $pengguna) {
            return null;
        }

        $ulasan = $resep->ulasan()
            ->where('user_id', $pengguna->id)
            ->first();

        if (! $ulasan) {
            return null;
        }

        return [
            'id' => $ulasan->id,
            'rating' => $ulasan->rating,
            'komentar' => $ulasan->komentar,
        ];
    }

    /**
     * Apakah resep ini sudah disimpan oleh pengunjung yang sedang login?
     *
     * Tamu selalu `false`, dan angka itu tidak pernah dipakai untuk memutus
     * apakah tombol "Simpan" tampil — tombolnya selalu tampil, hanya
     * perlakukannya berbeda: pengunjung belum login diarahkan ke halaman
     * masuk. Menyembunyikan tombol dari tamu justru menghapus satu-satunya
     * alasan baginya untuk mendaftar.
     */
    private function disimpan(Request $request, Resep $resep): bool
    {
        $pengguna = $request->user();

        if (! $pengguna) {
            return false;
        }

        return $pengguna->simpanan()->where('resep_id', $resep->id)->exists();
    }

    /**
     * Apakah pengunjung sudah menyukai target (resep/profil) tertentu?
     *
     * Tamu selalu `false` — sama seperti `disimpan()`, angka ini hanya
     * menentukan bentuk tombol (hati terisi/habis), bukan tampil tidaknya.
     */
    private function sudahSuka(Request $request, string $tipe, int $targetId): bool
    {
        $pengguna = $request->user();

        if (! $pengguna) {
            return false;
        }

        return $pengguna->sukaan()
            ->where('sukaable_type', $tipe)
            ->where('sukaable_id', $targetId)
            ->exists();
    }

    /**
     * Empat resep lain untuk blok "Resep lain" di kaki halaman detail.
     *
     * Sekategori dulu, supaya rekomendasinya memang dekat dengan yang sedang
     * dibaca. Bila kategori itu sendiri kurang dari empat resep, sisanya diisi
     * dari kategori lain — lebih berguna daripada memperlihatkan blok yang
     * setengah kosong. Hasil akhirnya boleh benar-benar kosong (misalnya saat
     * database berisi satu resep), dan halaman cukup menyembunyikan bloknya.
     *
     * @return Collection<int, Resep>
     */
    private function terkait(Resep $resep): Collection
    {
        $terkait = Resep::query()
            ->terbit()
            ->etalase()
            ->where('kategori', $resep->kategori)
            ->where('slug', '!=', $resep->slug)
            ->with('penulis:id,name,username,avatar_path')
            ->withCount('suka as jumlah_suka')
            ->withCount('disimpanOleh as jumlah_simpanan')
            ->take(self::JUMLAH_TERKAIT)
            ->get();

        if ($terkait->count() < self::JUMLAH_TERKAIT) {
            $sudahDipakai = $terkait->pluck('slug')->push($resep->slug);

            $terkait = $terkait->concat(
                Resep::query()
                    ->terbit()
                    ->etalase()
                    ->where('kategori', '!=', $resep->kategori)
                    ->whereNotIn('slug', $sudahDipakai)
                    ->with('penulis:id,name,username,avatar_path')
                    ->withCount('suka as jumlah_suka')
                    ->withCount('disimpanOleh as jumlah_simpanan')
                    ->take(self::JUMLAH_TERKAIT - $terkait->count())
                    ->get()
            );
        }

        return $terkait->values();
    }
}
