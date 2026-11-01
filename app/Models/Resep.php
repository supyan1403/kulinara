<?php

namespace App\Models;

use App\Enums\KategoriResep;
use App\Enums\SatuanDasar;
use App\Enums\StatusResep;
use App\Enums\TingkatKesulitan;
use App\Enums\UserRole;
use App\Services\KalkulasiGizi;
use Database\Factories\ResepFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'user_id',
    'judul',
    'slug',
    'deskripsi',
    'gambar',
    'daerah',
    'kategori',
    'tingkat_kesulitan',
    'status',
    'waktu_masak_menit',
    'porsi',
    'energi_kkal',
    'protein_g',
    'lemak_g',
    'karbohidrat_g',
    'gizi_mode',
    'hasil_total',
    'satuan_hasil',
    'alat',
    'gizi_cakupan',
    'bahan',
    'langkah',
    // Kolom kurasi — diisi Pengelola lewat KurasiController, bukan lewat
    // formulir Kreator, tapi tetap fillable agar update() bisa memakainya.
    'diajukan_at',
    'ditinjau_at',
    'ditinjau_oleh',
    'catatan_kurasi',
    'disunting_setelah_terbit_at',
    'unggulan',
    'urutan_unggulan',
])]
class Resep extends Model
{
    /** @use HasFactory<ResepFactory> */
    use HasFactory;

    /**
     * Nama tabel eksplisit — "resep" jamak tetap "resep" dalam bahasa Indonesia,
     * sehingga pluralisasi bawaan Laravel ("reseps") tidak berlaku.
     */
    protected $table = 'resep';

    /**
     * Nilai awal kolom ringkasan rating.
     *
     * Kolomnya sudah punya `default(0)` di basis data, tapi default itu hanya
     * berlaku saat baris dibaca kembali dari sana. Model yang baru dibuat
     * (`Resep::create()`, atau lewat factory) belum memuatnya, sehingga
     * atributnya null — dan kode yang membaca `jumlah_ulasan` untuk memutuskan
     * tampilan akan mendapat null, bukan angka. Menaruhnya di sini membuat
     * resep baru berperilaku sama dengan resep yang dibaca dari basis data.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'rata_rating' => 0,
        'jumlah_ulasan' => 0,
    ];

    /**
     * Slug dibuat otomatis dari judul saat resep pertama disimpan.
     *
     * Sengaja tidak diperbarui saat judul diedit — slug adalah identitas
     * permanen resep, menggantinya akan memutus tautan yang sudah dibagikan.
     */
    protected static function booted(): void
    {
        static::creating(function (Resep $resep): void {
            if (! $resep->slug) {
                $resep->slug = static::slugUnik($resep->judul);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kategori' => KategoriResep::class,
            'tingkat_kesulitan' => TingkatKesulitan::class,
            'status' => StatusResep::class,
            'energi_kkal' => 'decimal:2',
            'protein_g' => 'decimal:2',
            'lemak_g' => 'decimal:2',
            'karbohidrat_g' => 'decimal:2',
            'bahan' => 'array',
            'langkah' => 'array',
            'hasil_total' => 'decimal:2',
            // Nilai enum persis sama dengan SatuanDasar ('gram' | 'ml') —
            // sengaja dipakai ulang supaya tidak ada dua tipe yang isinya
            // kembar tapi namanya beda.
            'satuan_hasil' => SatuanDasar::class,
            'alat' => 'array',
            'gizi_cakupan' => 'integer',
            'diajukan_at' => 'datetime',
            'ditinjau_at' => 'datetime',
            'disunting_setelah_terbit_at' => 'datetime',
            'rata_rating' => 'decimal:2',
            'jumlah_ulasan' => 'integer',
            'unggulan' => 'boolean',
            'urutan_unggulan' => 'integer',
        ];
    }

    /**
     * Apakah resep ini memenuhi syarat "Standar".
     *
     * Syaratnya sengaja ketat — badge ini dipakai calon pembeli/penjual
     * sebagai tanda resep bisa direproduksi, jadi angkanya harus bisa
     * dipertanggungjawabkan:
     *
     *  1. Gizi dihitung OTOMATIS dari bahan (bukan diisi tangan).
     *  2. Cakupan perhitungan 100% — tak ada bahan "secukupnya" yang
     *     lolos dari hitungan.
     *  3. Hasil akhir (`hasil_total`) dicatat — ini yang memungkinkan
     *     rendemen dan skala produksi dihitung.
     */
    public function standarTerpenuhi(): bool
    {
        return $this->gizi_mode === 'otomatis'
            && $this->gizi_cakupan === 100
            && $this->hasil_total !== null;
    }

    /**
     * Rendemen: perbandingan hasil akhir terhadap total bahan masukan.
     *
     * Misalnya 1 kg beras + bumbu (total 1200 g) menghasilkan 900 g nasi
     * kering — rendemen 75%. Angka ini dipakai untuk menakar produksi
     * massal: tahu berapa bahan mentah yang perlu dibeli untuk target
     * hasil tertentu.
     *
     * Null bila syaratnya belum terpenuhi (hasil_total belum diisi atau
     * gizi tidak otomatis penuh).
     */
    public function rendemen(): ?float
    {
        if (! $this->standarTerpenuhi()) {
            return null;
        }

        $totalBahan = app(KalkulasiGizi::class)->totalGram($this->bahan);

        if ($totalBahan <= 0) {
            return null;
        }

        return round(((float) $this->hasil_total / $totalBahan) * 100, 1);
    }

    /**
     * Penulis resep (Kreator atau Pengelola).
     */
    public function penulis(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Pengelola yang memutuskan nasib pengajuan terakhir.
     *
     * Bisa null walaupun `ditinjau_at` terisi — misalnya akun peninjau
     * dihapus. Panel kurasi harus tahan terhadap itu.
     */
    public function peninjau(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditinjau_oleh');
    }

    /**
     * Baris penanda siapa saja yang menyimpan resep ini.
     *
     * Himpunan pengguna yang menyimpan bisa diambil lewat
     * `->disimpanOleh()->where('user_id', $id)->exists()`, yang dipakai
     * halaman detail untuk menentukan keadaan tombol "Simpan".
     */
    public function disimpanOleh(): HasMany
    {
        return $this->hasMany(Simpanan::class, 'resep_id');
    }

    /**
     * Ulasan yang diterima resep ini.
     */
    public function ulasan(): HasMany
    {
        return $this->hasMany(Ulasan::class, 'resep_id');
    }

    /**
     * Baris "suka resep" (like) yang diterima resep ini.
     *
     * `where('sukaable_type', ...)` menempel pada setiap hitungan sehingga
     * `withCount('suka as jumlah_suka')` tidak pernah ikut menghitung suka
     * profil yang kebetulan berbagi ID numerik.
     */
    public function suka(): HasMany
    {
        return $this->hasMany(Suka::class, 'sukaable_id')
            ->where('sukaable_type', Suka::RESEP);
    }

    /**
     * Skor pemeringkatan resep unggulan profil penulisnya.
     *
     * Tiga bahan, dimaksudkan supaya tidak satu pun yang bisa menominasi
     * sendirian:
     *
     *  - like      ×3 — apresiasi termurah dari pembaca, jadi berbobot paling
     *                   tinggi; tanpa pengali ini seperti hanya like yang
     *                   berarti dan ulasan jadi hiasan
     *  - rating × ulasan — rata-rata tanpa jumlah ulasan bisa dimenangkan
     *                   oleh satu penilaian bintang lima, jadi keduanya
     *                   dikalikan agar bobotnya sebanding dengan buktinya
     *  - simpanan  ×2 — tanda "aku akan masak ini nanti", nyata tapi tidak
     *                   seserius penilaian
     *
     * Fallback alami: saat belum ada yang punya like (semua nol), skor
     * menyusut sendiri ke rumus rating×ulasan + simpanan×2 — tanpa syarat
     * ambang yang bisa membuat seluruh bagian unggulan mendadak kosong.
     */
    public function skorUnggulan(): float
    {
        return ((int) $this->jumlah_suka) * 3
            + ((float) $this->rata_rating) * ((int) $this->jumlah_ulasan)
            + ((int) $this->jumlah_simpanan) * 2;
    }

    /**
     * Apakah resep ini sudah punya ulasan.
     *
     * Dipakai antarmuka untuk memilih antara menampilkan angka bintang atau
     * tulisan "Belum ada ulasan" — rata-rata 0 tanpa ulasan bukan berarti
     * penilaian terburuk, jadi `rata_rating` saja tidak cukup untuk memutuskan.
     */
    public function sudahDiulas(): bool
    {
        // `(int)` bukan hiasan: model yang baru dibuat belum memuat nilai
        // default dari basis data, sehingga atributnya masih null walau
        // kolomnya `default(0)`. Tanpa cast ini, resep baru akan mengembalikan
        // null dan pemanggil yang mengharapkan boolean mendapat kejutan.
        return (int) $this->jumlah_ulasan > 0;
    }

    /**
     * Hitung ulang `rata_rating` dan `jumlah_ulasan` dari isi tabel ulasan.
     *
     * Satu-satunya tempat kedua kolom itu ditulis. Dipanggil dari
     * `UlasanObserver` (saat ulasan dibuat/diubah/dihapus) dan dari `User`
     * (saat akun dihapus, yang menghapus ulasannya lewat ON DELETE CASCADE
     * tanpa memicu observer).
     *
     * Dihitung dari `AVG` di basis data, bukan dengan menambah atau mengurangi
     * angka yang tersimpan. Cara tambal-sulam mengakumulasi galat pembulatan
     * setiap kali ada perubahan, dan galat itu tidak pernah bisa hilang
     * sendiri.
     *
     * Memakai `forceFill` + `saveQuietly`, BUKAN `updateQuietly`. Alasannya
     * penting dan tidak kelihatan: `updateQuietly()` mengisi lewat `fill()`,
     * yang menghormati daftar `#[Fillable]` — dan `rata_rating` serta
     * `jumlah_ulasan` memang sengaja TIDAK ada di daftar itu, karena keduanya
     * kolom turunan yang tidak boleh diisi dari input pengguna. Akibatnya
     * `updateQuietly()` akan mengabaikan kedua nilai itu diam-diam: tidak ada
     * galat, tidak ada perubahan, dan ringkasan rating berhenti diperbarui
     * selamanya. `forceFill` menulis langsung tanpa memeriksa daftar itu —
     * tepat di sini aman, karena nilainya dihitung sendiri, bukan dari input.
     *
     * `saveQuietly` supaya tidak memicu event `saved` pada resep.
     */
    public function sinkronkanRating(): void
    {
        $agregat = Ulasan::query()
            ->where('resep_id', $this->id)
            ->selectRaw('COUNT(*) as jumlah, AVG(rating) as rata')
            ->first();

        $jumlah = (int) ($agregat->jumlah ?? 0);

        $this->forceFill([
            'jumlah_ulasan' => $jumlah,
            // Tanpa ulasan, rata-rata dibiarkan 0 — bukan null — supaya
            // kolomnya tidak perlu penanganan khusus di setiap pembacaan.
            'rata_rating' => $jumlah === 0
                ? 0
                : round((float) $agregat->rata, 2),
        ])->saveQuietly();
    }

    /**
     * Hanya resep yang layak tayang di halaman publik.
     */
    public function scopeTerbit(Builder $query): Builder
    {
        return $query->whereIn('status', [StatusResep::Terbit, StatusResep::Terverifikasi]);
    }

    /**
     * Hanya resep yang ditandai unggulan (pinned) di profil penulis.
     */
    public function scopeUnggulan(Builder $query): Builder
    {
        return $query->where('unggulan', true);
    }

    /**
     * Saringan direktori publik: status tayang + kategori + kata kunci.
     *
     * Dipakai sama persis oleh beranda dan halaman /resep, sehingga keduanya
     * tidak mungkin berbeda pandangan tentang resep mana yang layak tampil di
     * depan pengunjung. Resep berstatus Draft ikut tersaring di sini juga —
     * karena itu halaman detail cukup memakai scope ini dan Draft otomatis
     * berujung 404 tanpa perlu pemeriksaan terpisah.
     */
    public function scopeDirektori(Builder $query, ?KategoriResep $kategori, string $kata): Builder
    {
        return $this->scopeTerbit($query)
            ->when($kategori, fn (Builder $q) => $q->where('kategori', $kategori))
            ->when($kata !== '', fn (Builder $q) => $q->cari($kata));
    }

    /**
     * Saringan tingkat kesulitan. `null` berarti tanpa saringan.
     */
    public function scopeKesulitan(Builder $query, ?TingkatKesulitan $kesulitan): Builder
    {
        return $query->when($kesulitan, fn (Builder $q) => $q->where('tingkat_kesulitan', $kesulitan));
    }

    /**
     * Saringan durasi maksimal (menit). `null` atau `0` berarti tanpa saringan.
     */
    public function scopeMaksMenit(Builder $query, ?int $menit): Builder
    {
        return $query->when($menit && $menit > 0, fn (Builder $q) => $q->where('waktu_masak_menit', '<=', $menit));
    }

    /**
     * Saringan rating minimum. `null` atau `0` berarti tanpa saringan.
     */
    public function scopeMinRating(Builder $query, ?float $rating): Builder
    {
        return $query->when($rating && $rating > 0, fn (Builder $q) => $q->where('rata_rating', '>=', $rating));
    }

    /**
     * Saringan berdasarkan nama bahan. Kolom `bahan` adalah jsonb berisi
     * larik objek {nama, jumlah, satuan}, jadi pencarian mencocokkan `nama`
     * tiap elemen. Di PostgreSQL dipakai `jsonb_array_elements`; di SQLite
     * (unit test) fallback ke pencocokan LIKE pada teks JSON mentah.
     */
    public function scopeDenganBahan(Builder $query, string $bahan): Builder
    {
        $bahan = trim($bahan);

        if ($bahan === '') {
            return $query;
        }

        if ($query->getConnection()->getDriverName() === 'pgsql') {
            return $query->whereRaw(
                "EXISTS (SELECT 1 FROM jsonb_array_elements(bahan) AS b WHERE b->>'nama' ILIKE ?)",
                ["%{$bahan}%"]
            );
        }

        return $query->where('bahan', 'like', "%{$bahan}%");
    }

    /**
     * Saringan berdasarkan rujukan bahan (`bahan_id` di jsonb `bahan`).
     *
     * Lebih presisi daripada `scopeDenganBahan` yang menyerempet nama:
     * ini hanya menangkap resep yang benar-benar menyambung barisnya ke
     * bahan_pangan lewat Studio Dapur. Dipakai dropdown "Mengandung
     * bahan" di halaman /resep.
     *
     * Di SQLite (unit test) pencariannya jatuh ke LIKE pada teks JSON
     * mentah karena SQLite tidak punya operator jsonb.
     */
    public function scopeBahanId(Builder $query, ?int $bahanId): Builder
    {
        if (! $bahanId) {
            return $query;
        }

        if ($query->getConnection()->getDriverName() === 'pgsql') {
            return $query->whereRaw(
                "EXISTS (SELECT 1 FROM jsonb_array_elements(bahan) AS b WHERE (b->>'bahan_id')::int = ?)",
                [$bahanId]
            );
        }

        // json_encode tidak menaruh spasi setelah titik-koma, jadi polanya
        // `"bahan_id":7`. Angka diakhiri koma bila bukan kunci terakhir,
        // atau kurung tutup bila kunci terakhir — keduanya dicakup supaya
        // `7` tidak ikut mencocokkan `70`.
        return $query->where(function (Builder $inner) use ($bahanId) {
            $inner->where('bahan', 'like', '%"bahan_id":'.$bahanId.',%')
                ->orWhere('bahan', 'like', '%"bahan_id":'.$bahanId.'}%');
        });
    }

    /**
     * Antrean kurasi: resep yang sudah diajukan dan menunggu keputusan.
     *
     * Yang paling lama menunggu tampil lebih dulu — bukan yang terbaru.
     * Antrean harus menguras dari ujung yang paling menderita, kalau tidak
     * pengajuan lama bisa tertimbun selamanya di bawah kiriman baru.
     */
    public function scopeAntreanKurasi(Builder $query): Builder
    {
        return $query
            ->where('status', StatusResep::Diajukan)
            ->orderBy('diajukan_at');
    }

    /**
     * Resep tayang yang isinya diubah setelah lolos kurasi.
     *
     * Suntingan tetap berlaku langsung (Kreator tidak dihukum karena
     * memperbaiki salah ketik), tapi Pengelola berhak melihatnya kembali.
     */
    public function scopeDisuntingSetelahTerbit(Builder $query): Builder
    {
        return $query->whereNotNull('disunting_setelah_terbit_at');
    }

    /**
     * Urutan etalase: resep berfoto didahulukan, lalu yang terbaru.
     *
     * Dipakai di tiga tempat yang menghadap pengunjung — grid beranda,
     * direktori /resep, dan blok resep terkait di halaman detail — supaya
     * urutannya tidak mungkin berbeda antarhalaman.
     */
    public function scopeEtalase(Builder $query): Builder
    {
        return $query
            ->orderByRaw('CASE WHEN gambar IS NULL THEN 1 ELSE 0 END')
            ->latest();
    }

    /**
     * Ressep yang berada dalam kelolaan pengguna ini.
     *
     * Pengelola melihat semua resep (untuk moderasi); peran lain hanya miliknya.
     */
    public function scopeMilik(Builder $query, User $user): Builder
    {
        if ($user->role === UserRole::Pengelola) {
            return $query;
        }

        return $query->where('user_id', $user->id);
    }

    /**
     * Pencarian teks penuh memakai kolom generated `pencarian` (tsvector).
     *
     * Hanya tersedia di PostgreSQL; di SQLite fallback ke pencarian LIKE biasa
     * supaya unit test tetap jalan.
     */
    public function scopeCari(Builder $query, string $kata): Builder
    {
        $kata = trim($kata);

        if ($kata === '') {
            return $query;
        }

        if ($query->getConnection()->getDriverName() === 'pgsql') {
            return $query->whereRaw("pencarian @@ to_tsquery('simple', ?)", [$this->tsquery($kata)]);
        }

        return $query->where(function (Builder $inner) use ($kata) {
            $inner->where('judul', 'like', "%{$kata}%")
                ->orWhere('deskripsi', 'like', "%{$kata}%");
        });
    }

    /**
     * Susun istilah pencarian menjadi tsquery yang aman (tiap kata di-AND-kan).
     */
    private function tsquery(string $kata): string
    {
        $bersih = preg_split('/\s+/', $kata, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return collect($bersih)
            ->map(fn (string $k) => $k.':*')
            ->implode(' & ');
    }

    /**
     * Slug unik: "rendang", "rendang-2", "rendang-3", … dst.
     */
    private static function slugUnik(string $judul): string
    {
        $dasar = Str::slug($judul) ?: 'resep';
        $slug = $dasar;
        $n = 2;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = "{$dasar}-{$n}";
            $n++;
        }

        return $slug;
    }
}
