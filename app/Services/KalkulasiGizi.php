<?php

namespace App\Services;

use App\Models\Bahan;

/**
 * Kalkulasi nilai gizi otomatis dari daftar bahan resep.
 *
 * Prinsip kerjanya satu: setiap baris bahan diubah dulu menjadi GRAM,
 * lalu gram itu dikalikan dengan nilai gizi bahan bersangkutan (per gram),
 * dijumlahkan untuk seluruh resep, dan akhirnya dibagi jumlah porsi.
 *
 * Yang membuat rumit bukan hitungannya, melainkan TAKARAN dapur. Resep
 * menulis "3 siung bawang" atau "2 sdm minyak", sementara nilai gizi
 * selalu per 100 gram. Jembatannya ada dua kolom di `bahan_pangan`:
 *
 *   - `faktor_densitas`  — gram per mililiter, untuk mengubah cairan
 *                          (ml, liter, sdt, sdm) menjadi berat.
 *   - `berat_per_unit`   — gram per satuan takaran (siung, butir, buah),
 *                          untuk mengubah hitungan "buah" menjadi berat.
 *
 * Baris yang TIDAK bisa dikonversi — "secukupnya", satuan tak dikenal,
 * atau bahan yang tidak ada di rujukan — tidak dibuang diam-diam. Ia
 * dilaporkan lewat `takTerhitung` dan menurunkan `cakupan`, supaya
 * penulis resep tahu persis berapa persen angka gizinya yang bisa
 * dipertanggungjawabkan.
 */
class KalkulasiGizi
{
    /**
     * Volume sendok takar, dalam mililiter.
     *
     * Standar takaran dapur internasional: sdt = 5 ml, sdm = 15 ml.
     */
    private const SENDOK_TEH_ML = 5;

    private const SENDOK_MAKAN_ML = 15;

    /**
     * Satuan yang takarannya memang sudah berat — langsung dipakai apa
     * adanya tanpa melalui densitas.
     */
    private const SATUAN_BERAT = ['gram', 'gr', 'g', 'kg', 'kilogram'];

    /**
     * Satuan takaran berbasis "hitungan benda" — butuh `berat_per_unit`
     * dari bahan bersangkutan.
     */
    private const SATUAN_BUTIR = [
        'buah',
        'siung',
        'butir',
        'ruas',
        'lembar',
        'ikat',
        'genggam',
        'batang',
        'ekor',
        'iris',
        'slice',
        'sachet',
        'bungkus',
    ];

    /**
     * Hitung nilai gizi per porsi dari daftar bahan resep.
     *
     * @param  array<int, array<string, mixed>>  $bahanResep  Isi kolom jsonb `bahan` resep.
     * @return array{
     *     energi_kkal: float, protein_g: float, lemak_g: float, karbohidrat_g: float,
     *     cakupan: int, totalBahan: int, terhitung: int,
     *     takTerhitung: array<int, array{nama: string, alasan: string}>
     * }
     */
    public function hitung(array $bahanResep, int $porsi): array
    {
        $porsi = max(1, $porsi);

        // Satu query untuk seluruh resep — bukan satu query per baris.
        $master = $this->masterUntuk();

        $total = [
            'energi_kkal' => 0.0,
            'protein_g' => 0.0,
            'lemak_g' => 0.0,
            'karbohidrat_g' => 0.0,
        ];

        $terhitung = 0;
        $takTerhitung = [];
        $totalBahan = count($bahanResep);

        foreach ($bahanResep as $baris) {
            $nama = (string) ($baris['nama'] ?? '(tanpa nama)');
            $bahan = $this->resolusi($baris, $master);

            if (! $bahan instanceof Bahan) {
                $takTerhitung[] = ['nama' => $nama, 'alasan' => $bahan];

                continue;
            }

            $gram = $this->keGram($baris, $bahan);

            if ($gram === null) {
                $takTerhitung[] = ['nama' => $nama, 'alasan' => $this->alasanKonversi($baris)];

                continue;
            }

            $gizi = $bahan->giziPerGram();

            foreach ($total as $kunci => $_) {
                $total[$kunci] += $gram * $gizi[$kunci];
            }

            $terhitung++;
        }

        $cakupan = $totalBahan === 0
            ? 0
            : (int) round(($terhitung / $totalBahan) * 100);

        return [
            'energi_kkal' => round($total['energi_kkal'] / $porsi, 2),
            'protein_g' => round($total['protein_g'] / $porsi, 2),
            'lemak_g' => round($total['lemak_g'] / $porsi, 2),
            'karbohidrat_g' => round($total['karbohidrat_g'] / $porsi, 2),
            'cakupan' => $cakupan,
            'totalBahan' => $totalBahan,
            'terhitung' => $terhitung,
            'takTerhitung' => $takTerhitung,
        ];
    }

    /**
     * Total berat bahan dalam gram — semua baris yang bisa dikonversi.
     *
     * Dipakai menghitung rendemen (hasil akhir ÷ total bahan) dan
     * skala produksi: dua nilai ini yang membuat resep bisa
     * direproduksi di dapur produksi, bukan hanya di dapur rumah.
     *
     * Baris yang tak bisa dikonversi diabaikan di sini — mereka memang
     * tidak punya berat pasti, dan itulah sebabnya `cakupan` dari
     * `hitung()` ikut dilaporkan.
     *
     * @param  array<int, array<string, mixed>>  $bahanResep
     */
    public function totalGram(array $bahanResep): float
    {
        $master = $this->masterUntuk();
        $total = 0.0;

        foreach ($bahanResep as $baris) {
            $bahan = $this->resolusi($baris, $master);

            if (! $bahan instanceof Bahan) {
                continue;
            }

            $gram = $this->keGram($baris, $bahan);

            if ($gram !== null) {
                $total += $gram;
            }
        }

        return round($total, 2);
    }

    /**
     * Konversi satu baris bahan menjadi gram. Null bila tak bisa dikonversi.
     */
    private function keGram(array $baris, Bahan $bahan): ?float
    {
        $jumlah = (float) ($baris['jumlah'] ?? 0);
        $satuan = mb_strtolower(trim((string) ($baris['satuan'] ?? 'gram')));

        if ($jumlah <= 0) {
            return null;
        }

        $densitas = (float) $bahan->faktor_densitas;

        // Berat — dipakai apa adanya.
        if (in_array($satuan, self::SATUAN_BERAT, true)) {
            return $satuan === 'kg' || $satuan === 'kilogram'
                ? $jumlah * 1000
                : $jumlah;
        }

        // Cairan — diubah ke gram lewat densitas bahan.
        return match ($satuan) {
            'ml', 'mililiter', 'cc' => $jumlah * $densitas,
            'liter', 'l' => $jumlah * 1000 * $densitas,
            'sdt', 'sendok teh' => self::SENDOK_TEH_ML * $jumlah * $densitas,
            'sdm', 'sendok makan' => self::SENDOK_MAKAN_ML * $jumlah * $densitas,
            default => $this->keGramButir($jumlah, $satuan, $bahan),
        };
    }

    /**
     * Konversi satuan "hitungan benda" (siung, butir, buah) menjadi gram.
     */
    private function keGramButir(float $jumlah, string $satuan, Bahan $bahan): ?float
    {
        if (! in_array($satuan, self::SATUAN_BUTIR, true)) {
            return null;
        }

        $berat = $bahan->berat_per_unit;

        // Bahan tanpa `berat_per_unit` tidak bisa mengubah "1 siung" jadi
        // gram — dan itu memang tidak boleh ditebak, karena berat satu
        // siung bawang dan satu siung bawang putih berbeda jauh.
        if ($berat === null) {
            return null;
        }

        return $jumlah * (float) $berat;
    }

    /**
     * Pasangkan satu baris bahan dengan rujukannya.
     *
     * Mengembalikan objek `Bahan` bila cocok, atau STRING alasan bila tidak —
     * bukan null — supaya pemanggil bisa langsung menampilkan sebabnya ke
     * penulis resep tanpa menebak-nebak.
     */
    private function resolusi(array $baris, array $master): Bahan|string
    {
        $bahanId = $baris['bahan_id'] ?? null;

        if ($bahanId !== null && isset($master[(int) $bahanId])) {
            return $master[(int) $bahanId];
        }

        // Bahan tanpa rujukan: coba cocokkan namanya ke rujukan. Pencocokan
        // longgar (tanpa huruf besar/kecil, tanpa spasi berlebih) supaya
        // "Bawang Merah" dan "bawang  merah" dianggap sama.
        $nama = $this->namaNormal((string) ($baris['nama'] ?? ''));

        if ($nama === '') {
            return 'nama bahan kosong';
        }

        foreach ($master as $bahan) {
            if ($this->namaNormal($bahan->nama) === $nama) {
                return $bahan;
            }
        }

        return 'tidak ada di rujukan bahan';
    }

    /**
     * Ambil rujukan bahan yang bisa jadi pasangan untuk daftar bahan ini.
     *
     * Seluruh baris `bahan_pangan` yang AKTIF diambil dalam satu query,
     * lalu pencocokan nama dilakukan di PHP memakai nama ternormalisasi.
     *
     * Sengaja tidak memakai `LIKE`/`whereIn` berbasis nama: pencocokan
     * longgar ("bawang  merah" → "Bawang Merah") tidak bisa diekspresikan
     * dengan aman lewat SQL tanpa ekstensi trigram, sementara kecocokan
     * nama persis akan melewatkan penulis yang mengetik spasi ganda atau
     * huruf kecil. Tabel rujukan ini kecil dan terbatas (ratusan baris,
     * bukan jutaan), jadi sekali baca ke memori untuk tiap penyimpanan
     * resep adalah harga yang wajar — dan nilainya bisa langsung dipakai
     * ulang untuk semua baris tanpa query tambahan.
     *
     * @return array<int, Bahan>
     */
    private function masterUntuk(): array
    {
        // Di-key by id supaya `resolusi()` bisa mencari langsung saat ada
        // `bahan_id`, sekaligus tetap bisa di-foreach untuk pencocokan nama.
        return Bahan::query()->aktif()->get()->keyBy('id')->all();
    }

    /**
     * Alasan konversi gagal, dalam bahasa manusia.
     */
    private function alasanKonversi(array $baris): string
    {
        $satuan = mb_strtolower(trim((string) ($baris['satuan'] ?? '')));

        if ($satuan === 'secukupnya' || $satuan === '') {
            return 'takaran "secukupnya" tidak bisa dihitung otomatis';
        }

        return "satuan \"{$satuan}\" tidak dikenal";
    }

    /**
     * Normalkan nama bahan untuk pencocokan longgar.
     */
    private function namaNormal(string $nama): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($nama)) ?? '');
    }
}
