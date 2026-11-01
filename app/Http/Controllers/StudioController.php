<?php

namespace App\Http\Controllers;

use App\Enums\KategoriResep;
use App\Enums\StatusResep;
use App\Enums\TingkatKesulitan;
use App\Enums\UserRole;
use App\Http\Requests\StoreResepRequest;
use App\Http\Requests\UpdateResepRequest;
use App\Models\Bahan;
use App\Models\Resep;
use App\Models\User;
use App\Services\KalkulasiGizi;
use App\Services\SupabaseStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class StudioController extends Controller
{
    /**
     * Daftar resep dalam kelolaan pengguna — Pengelola melihat semua resep.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Studio/Index', [
            'resep' => Resep::query()
                ->milik($request->user())
                ->with('penulis:id,name,username')
                // Reputasi tiap resep di mata pembaca: berapa orang yang
                // menyukai dan menyimpannya. Dihitung via withCount (dua query
                // turunan, bukan N+1) sehingga tiap baris daftar bisa
                // memamerkan "resep ini dipakai orang" tanpa query tambahan.
                ->withCount('suka as jumlah_suka')
                ->withCount('disimpanOleh as jumlah_simpanan')
                ->latest()
                ->get(),
            'kategoriTersedia' => $this->opsi(KategoriResep::class),
            'statistik' => $this->statistik($request->user()),
        ]);
    }

    /**
     * Formulir resep baru.
     */
    public function create(Request $request): Response
    {
        return $this->formulir($request, null);
    }

    /**
     * Simpan resep baru. Slug dibuat otomatis oleh model.
     */
    public function store(StoreResepRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Pengguna TIDAK boleh memilih status tayang. Resep baru selalu masuk
        // draft; menayangkannya adalah keputusan Pengelola lewat antrean
        // kurasi. Tanpa paksaan ini, kurasi bisa dilewati seluruhnya — cukup
        // pilih "Terbit" di formulir. Pengelola dikecualikan: ia yang memang
        // berhak menerbitkan, dan memaksanya melewati antreannya sendiri
        // tidak melindungi siapa pun.
        if ($request->user()->role !== UserRole::Pengelola) {
            $data['status'] = StatusResep::Draft;
        }

        $data['user_id'] = $request->user()->id;
        $data['gambar'] = $this->simpanGambar($request);
        $data = $this->terapkanGizi($data);

        Resep::query()->create($data);

        return to_route('studio.index')
            ->with('success', 'Resep tersimpan sebagai draft. Ajukan bila sudah siap ditinjau.');
    }

    /**
     * Ajukan draft ke antrean kurasi Pengelola.
     *
     * Hanya draft milik sendiri yang bisa diajukan. Resep yang sudah menunggu
     * tidak bisa diajukan dua kali, dan resep yang sudah tayang tidak perlu
     * melalui jalur ini sama sekali.
     */
    public function ajukanKurasi(Request $request, Resep $resep): RedirectResponse
    {
        Gate::authorize('update', $resep);

        if (! $resep->status->bisaDiajukan()) {
            return back()->with('error', 'Hanya resep berstatus Draft yang bisa diajukan.');
        }

        $resep->update([
            'status' => StatusResep::Diajukan,
            'diajukan_at' => now(),
            // Pengajuan ulang menghapus alasan penolakan sebelumnya supaya
            // penulis tidak melihat catatan lama pada versi yang baru.
            'catatan_kurasi' => null,
        ]);

        return back()->with('success', 'Resep diajukan. Pengelola akan meninjaunya.');
    }

    /**
     * Tarik kembali pengajuan yang belum ditinjau.
     *
     * Selama masih di antrean, penulis tetap pemilik karyanya — kalau ia
     * sadar ada yang salah, ia harus bisa menghentikannya sebelum Pengelola
     * sempat meninjau versi yang keliru.
     */
    public function tarikKurasi(Request $request, Resep $resep): RedirectResponse
    {
        Gate::authorize('update', $resep);

        if (! $resep->status->menungguKurasi()) {
            return back()->with('error', 'Resep ini tidak sedang menunggu kurasi.');
        }

        $resep->update([
            'status' => StatusResep::Draft,
            'diajukan_at' => null,
        ]);

        return back()->with('success', 'Pengajuan ditarik. Resep kembali menjadi draft.');
    }

    /**
     * Formulir ubah resep.
     */
    public function edit(Request $request, Resep $resep): Response
    {
        Gate::authorize('update', $resep);

        return $this->formulir($request, $resep);
    }

    /**
     * Perbarui resep — gambar lama diganti hanya bila ada unggahan baru.
     */
    public function update(UpdateResepRequest $request, Resep $resep): RedirectResponse
    {
        $data = $request->validated();
        unset($data['gambar']);

        // Suntingan pada resep yang SUDAH tayang langsung berlaku — Kreator
        // tidak dihukum karena memperbaiki salah ketik. Tapi jejaknya dicatat
        // supaya Pengelola bisa meninjau ulang bila isinya berubah banyak.
        // Tanpa penanda ini, kurasi bisa diakali: ajukan versi bersih, lolos,
        // lalu ganti seluruh isinya.
        $sebelumnyaTayang = $resep->status->isPublic();

        if ($request->hasFile('gambar')) {
            $this->hapusGambar($resep->gambar);
            $data['gambar'] = $this->simpanGambar($request);
        }

        // Pengguna tidak boleh menaikkan status lewat formulir sunting.
        // `statusDiizinkan()` sudah membatasi pilihannya, tapi nilai kiriman
        // tetap harus dipaksa aman bila permintaannya dikirim di luar UI.
        if ($request->user()->role !== UserRole::Pengelola) {
            unset($data['status']);
        }

        if ($sebelumnyaTayang) {
            $data['disunting_setelah_terbit_at'] = now();
        }

        $data = $this->terapkanGizi($data);

        $resep->update($data);

        return to_route('studio.index')->with('success', 'Resep berhasil diperbarui.');
    }

    /**
     * Hapus resep beserta berkas gambarnya.
     */
    public function destroy(Request $request, Resep $resep): RedirectResponse
    {
        Gate::authorize('delete', $resep);

        $this->hapusGambar($resep->gambar);
        $resep->delete();

        return to_route('studio.index')->with('success', 'Resep berhasil dihapus.');
    }

    /**
     * Satu halaman formulir dipakai untuk mode buat dan ubah.
     */
    private function formulir(Request $request, ?Resep $resep): Response
    {
        return Inertia::render('Studio/Form', [
            'resep' => $resep,
            'kategoriTersedia' => $this->opsi(KategoriResep::class),
            'kesulitanTersedia' => $this->opsi(TingkatKesulitan::class),
            'statusDiizinkan' => StatusResep::diizinkanUntuk($request->user()->role, $resep?->status),
            // Rujukan bahan untuk autocomplete di daftar bahan, plus
            // satuan_dasar & berat_per_unit yang dipakai pratinjau gizi.
            'bahanTersedia' => Bahan::query()
                ->aktif()
                ->orderBy('nama')
                ->get(['id', 'kode', 'nama', 'kategori', 'satuan_dasar', 'faktor_densitas', 'berat_per_unit', 'satuan_takaran', 'energi_kkal', 'protein_g', 'lemak_g', 'karbohidrat_g'])
                ->map(fn (Bahan $b) => [
                    'id' => $b->id,
                    'kode' => $b->kode,
                    'nama' => $b->nama,
                    'kategori' => $b->kategori->value,
                    'satuan_dasar' => $b->satuan_dasar->value,
                    'faktor_densitas' => (float) $b->faktor_densitas,
                    'berat_per_unit' => $b->berat_per_unit !== null ? (float) $b->berat_per_unit : null,
                    'satuan_takaran' => $b->satuan_takaran,
                    'energi_kkal' => (float) $b->energi_kkal,
                    'protein_g' => (float) $b->protein_g,
                    'lemak_g' => (float) $b->lemak_g,
                    'karbohidrat_g' => (float) $b->karbohidrat_g,
                ])
                ->values(),
        ]);
    }

    /**
     * Isi empat kolom gizi dari daftar bahan bila mode otomatis.
     *
     * Mode manual dibiarkan apa adanya — nilai kiriman penulis resep yang
     * dipakai, karena memang itu gunanya mode manual: mengisi sendiri saat
     * ada bahan yang tak bisa dihitung (mis. "secukupnya").
     *
     * Kolom `gizi_mode` ikut disimpan supaya formulir sunting tahu mode
     * awal resep — tanpa itu, membuka resep lama akan selalu terlihat
     * otomatis walaupun nilainya diisi tangan.
     */
    private function terapkanGizi(array $data): array
    {
        $mode = $data['gizi_mode'] ?? 'otomatis';
        $data['gizi_mode'] = $mode;

        // Mode manual: nilai gizi kiriman penulis dipertahankan, dan
        // cakupan diset 0 — supaya badge "Standar" tidak muncul pada
        // resep yang gizinya diisi tangan (tidak bisa diverifikasi).
        if ($mode === 'manual') {
            $data['gizi_cakupan'] = 0;

            return $data;
        }

        $hasil = app(KalkulasiGizi::class)->hitung(
            $data['bahan'] ?? [],
            (int) ($data['porsi'] ?? 1),
        );

        $data['gizi_cakupan'] = $hasil['cakupan'];

        $data['energi_kkal'] = $hasil['energi_kkal'];
        $data['protein_g'] = $hasil['protein_g'];
        $data['lemak_g'] = $hasil['lemak_g'];
        $data['karbohidrat_g'] = $hasil['karbohidrat_g'];

        return $data;
    }

    /**
     * Opsi enum untuk select/radio di frontend: [{value, label}, …].
     *
     * @return array<int, array<string, string>>
     */
    private function opsi(string $enum): array
    {
        return collect($enum::cases())
            ->map(fn ($case) => ['value' => $case->value, 'label' => $case->label()])
            ->values()
            ->all();
    }

    /**
     * Angka ringkas untuk kartu di halaman daftar.
     *
     * @return array<string, int>
     */
    private function statistik(User $user): array
    {
        $dasar = Resep::query()->milik($user);
        $tayang = (clone $dasar)
            ->whereIn('status', [StatusResep::Terbit, StatusResep::Terverifikasi]);

        return [
            'total' => (clone $dasar)->count(),
            'terbit' => (clone $tayang)->count(),
            'draft' => (clone $dasar)->where('status', StatusResep::Draft)->count(),
            'menunggu' => (clone $dasar)->where('status', StatusResep::Diajukan)->count(),

            // ── Kinerja di mata pembaca ──
            // Semua dihitung hanya dari resep TAYANG: resep draft belum bisa
            // disukai/disimpan siapa pun, jadi menghitungnya hanya menimbulkan
            // angka yang tidak bisa dibuktikan.
            //
            // `whereColumn('resep.user_id', ...)` — bukan scope milik() — karena
            // setelah JOIN, `user_id` polos ambigu (ada di kedua tabel) dan
            // dijatuhkan PostgreSQL dengan "ambiguous column reference".
            'suka' => Resep::query()
                ->where('resep.user_id', $user->id)
                ->whereIn('resep.status', [StatusResep::Terbit, StatusResep::Terverifikasi])
                ->join('suka', 'suka.sukaable_id', '=', 'resep.id')
                ->where('suka.sukaable_type', \App\Models\Suka::RESEP)
                ->count(),
            'simpanan' => Resep::query()
                ->where('resep.user_id', $user->id)
                ->whereIn('resep.status', [StatusResep::Terbit, StatusResep::Terverifikasi])
                ->join('simpanan', 'simpanan.resep_id', '=', 'resep.id')
                ->count(),
            'unggulan' => (clone $tayang)->where('unggulan', true)->count(),
        ];
    }

    /**
     * Simpan unggahan gambar resep ke bucket Supabase Storage.
     *
     * Kolom `gambar` kini berisi dua bentuk: URL absolut (unggahan baru,
     * Supabase) dan path relatif (foto bawaan images/landing/ serta unggahan
     * lama). Keduanya diteruskan apa adanya oleh resolver frontend —
     * `urlGambar()` dan `sumberGambar()` (FotoResep.jsx) sama-sama
     * mengenali URL absolut. Jangan menambah pemanggilan <img> tanpa
     * melalui keduanya — path relatif dihitung terhadap halaman aktif dan
     * berujung 404 di rute bersarang seperti /resep/{slug}.
     */
    private function simpanGambar(Request $request): ?string
    {
        if (! $request->hasFile('gambar')) {
            return null;
        }

        // Unggahan baru masuk bucket Supabase Storage — FS container Vercel
        // read-only kecuali /tmp, jadi tulisan ke public/ tidak durable.
        // Nilai yang dikembalikan berupa URL publik; frontend meneruskannya
        // apa adanya lewat urlGambar() yang sudah mengenali URL absolut.
        return SupabaseStorage::make()->upload($request->file('gambar'), 'resep');
    }

    /**
     * Hapus berkas gambar hasil unggahan — dari bucket Supabase (unggahan
     * baru) atau dari public/ (unggahan lama yang masih path relatif).
     *
     * Foto bawaan di images/landing/ sengaja tidak disentuh karena dipakai
     * bersama oleh halaman utama.
     */
    private function hapusGambar(?string $path): void
    {
        if ($path === null) {
            return;
        }

        if (str_starts_with($path, 'images/resep/')) {
            File::delete(public_path($path));
        } else {
            SupabaseStorage::make()->delete($path);
        }
    }
}
