<?php

namespace Tests\Unit;

use App\Models\Bahan;
use App\Services\KalkulasiGizi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KalkulasiGiziTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Berat langsung (gram) tidak perlu konversi apa pun — nilai gizi
     * bahan dikalikan jumlah gram persis seperti yang ditulis penulis.
     */
    public function test_konversi_gram_langsung(): void
    {
        Bahan::factory()->create([
            'nama' => 'Beras putih',
            'energi_kkal' => 360,   // per 100 g
            'protein_g' => 10,      // per 100 g, dibulatkan supaya hitungan gampang dicek
            'lemak_g' => 0,
            'karbohidrat_g' => 0,
        ]);

        $hasil = (new KalkulasiGizi)->hitung([
            ['nama' => 'Beras putih', 'jumlah' => 300, 'satuan' => 'gram'],
        ], 2);

        // 300 g beras = 3 × nilai per 100 g. Dibagi 2 porsi.
        $this->assertEqualsWithDelta(540, $hasil['energi_kkal'], 0.01);
        $this->assertEqualsWithDelta(15, $hasil['protein_g'], 0.01);
        $this->assertSame(100, $hasil['cakupan']);
        $this->assertSame(1, $hasil['terhitung']);
        $this->assertSame([], $hasil['takTerhitung']);
    }

    /**
     * Kilogram harus dikonversi ke gram dulu sebelum dikalikan nilai gizi.
     */
    public function test_konversi_kilogram_ke_gram(): void
    {
        Bahan::factory()->create([
            'nama' => 'Gula pasir',
            'energi_kkal' => 387,
            'protein_g' => 0,
            'lemak_g' => 0,
            'karbohidrat_g' => 0,
        ]);

        $hasil = (new KalkulasiGizi)->hitung([
            ['nama' => 'Gula pasir', 'jumlah' => 1, 'satuan' => 'kg'],
        ], 1);

        // 1 kg = 1000 g = 10 × nilai per 100 g.
        $this->assertEqualsWithDelta(3870, $hasil['energi_kkal'], 0.01);
    }

    /**
     * Bahan cair: mililiter diubah ke gram lewat `faktor_densitas` bahan
     * sebelum dikalikan nilai gizi (yang per 100 ml).
     */
    public function test_konversi_mililiter_memakai_densitas(): void
    {
        Bahan::factory()->cair()->create([
            'nama' => 'Santan kental',
            'energi_kkal' => 190,   // per 100 ml
            'faktor_densitas' => 0.9,
        ]);

        $hasil = (new KalkulasiGizi)->hitung([
            ['nama' => 'Santan kental', 'jumlah' => 200, 'satuan' => 'ml'],
        ], 2);

        // 200 ml × 0,9 g/ml = 180 gram. Tapi nilai gizi santan per 100 ML,
        // jadi 200 ml = 2 × nilai per 100 ml = 380 kkal. Dibagi 2 porsi.
        $this->assertEqualsWithDelta(190, $hasil['energi_kkal'], 0.01);
    }

    /**
     * Sendok takar dapur distandarkan: 1 sdt = 5 ml, 1 sdm = 15 ml,
     * lalu dikalikan densitas bahan untuk jadi gram.
     */
    public function test_konversi_sendok_teh_dan_makan(): void
    {
        Bahan::factory()->cair()->create([
            'nama' => 'Minyak goreng',
            'satuan_dasar' => \App\Enums\SatuanDasar::Mililiter,
            'energi_kkal' => 884,   // per 100 ml
            'faktor_densitas' => 0.92,
        ]);

        // 1 sdm = 15 ml → 15 × 884/100 = 132,6 kkal.
        $sdm = (new KalkulasiGizi)->hitung([
            ['nama' => 'Minyak goreng', 'jumlah' => 1, 'satuan' => 'sdm'],
        ], 1);

        $this->assertEqualsWithDelta(132.6, $sdm['energi_kkal'], 0.05);

        // 2 sdt = 10 ml → 10 × 884/100 = 88,4 kkal.
        $sdt = (new KalkulasiGizi)->hitung([
            ['nama' => 'Minyak goreng', 'jumlah' => 2, 'satuan' => 'sdt'],
        ], 1);

        $this->assertEqualsWithDelta(88.4, $sdt['energi_kkal'], 0.05);
    }

    /**
     * Satuan "butir" memakai `berat_per_unit` bahan. Tanpa kolom ini,
     * "1 siung" tidak boleh ditebak nilainya.
     */
    public function test_konversi_butir_memakai_berat_per_unit(): void
    {
        Bahan::factory()->butiran()->create([
            'nama' => 'Bawang merah',
            'energi_kkal' => 72,     // per 100 g
            'berat_per_unit' => 7,   // 1 siung = 7 g
        ]);

        $hasil = (new KalkulasiGizi)->hitung([
            ['nama' => 'Bawang merah', 'jumlah' => 5, 'satuan' => 'siung'],
        ], 1);

        // 5 siung × 7 g = 35 g → 35/100 × 72 = 25,2 kkal.
        $this->assertEqualsWithDelta(25.2, $hasil['energi_kkal'], 0.01);
        $this->assertSame(100, $hasil['cakupan']);
    }

    /**
     * "Secukupnya" tidak bisa dihitung — tapi TIDAK boleh dibuang diam-diam.
     * Ia dilaporkan lewat `takTerhitung` dan menurunkan cakupan.
     */
    public function test_secukupnya_dilaporkan_bukan_dihitung(): void
    {
        Bahan::factory()->create(['nama' => 'Beras putih', 'energi_kkal' => 360]);
        Bahan::factory()->create(['nama' => 'Garam dapur', 'energi_kkal' => 0]);

        $hasil = (new KalkulasiGizi)->hitung([
            ['nama' => 'Beras putih', 'jumlah' => 300, 'satuan' => 'gram'],
            ['nama' => 'Garam dapur', 'jumlah' => 1, 'satuan' => 'secukupnya'],
        ], 1);

        $this->assertSame(50, $hasil['cakupan']);
        $this->assertSame(1, $hasil['terhitung']);
        $this->assertCount(1, $hasil['takTerhitung']);
        $this->assertSame('Garam dapur', $hasil['takTerhitung'][0]['nama']);
    }

    /**
     * Bahan yang tidak ada di rujukan sama sekali tetap dihitung kalau
     * NAMANYA bisa dicocokkan — pencocokan tanpa peduli huruf besar/kecil.
     */
    public function test_pencocokan_nama_tanpa_peduli_huruf(): void
    {
        Bahan::factory()->create([
            'nama' => 'Bawang Merah',
            'energi_kkal' => 72,
            'protein_g' => 0,
            'lemak_g' => 0,
            'karbohidrat_g' => 0,
            'berat_per_unit' => 7,
            'satuan_takaran' => 'siung',
        ]);

        $hasil = (new KalkulasiGizi)->hitung([
            ['nama' => 'bawang  merah', 'jumlah' => 2, 'satuan' => 'siung'],
        ], 1);

        $this->assertSame(100, $hasil['cakupan']);
        // 2 siung × 7 g = 14 g → 14/100 × 72 = 10,08 kkal.
        $this->assertEqualsWithDelta(10.08, $hasil['energi_kkal'], 0.01);
    }

    /**
     * Bahan yang tidak dikenal sama sekali tidak dihitung, alasan jelas.
     */
    public function test_bahan_tidak_dikenal_dilaporkan(): void
    {
        $hasil = (new KalkulasiGizi)->hitung([
            ['nama' => 'Bumbu rahasia nenek', 'jumlah' => 2, 'satuan' => 'sachet'],
        ], 1);

        $this->assertSame(0, $hasil['cakupan']);
        $this->assertSame(0, $hasil['terhitung']);
        $this->assertSame('tidak ada di rujukan bahan', $hasil['takTerhitung'][0]['alasan']);
    }

    /**
     * Resep dengan nol porsi (atau negatif) tidak boleh membagi dengan nol.
     */
    public function test_porsi_nol_diperlakukan_satu(): void
    {
        Bahan::factory()->create(['nama' => 'Beras putih', 'energi_kkal' => 360]);

        $hasil = (new KalkulasiGizi)->hitung([
            ['nama' => 'Beras putih', 'jumlah' => 100, 'satuan' => 'gram'],
        ], 0);

        $this->assertEqualsWithDelta(360, $hasil['energi_kkal'], 0.01);
    }

    /**
     * Gizi beberapa bahan dijumlahkan, lalu dibagi porsi — prinsip dasar
     * perhitungan per porsi.
     */
    public function test_gizi_beberapa_bahan_dijumlahkan_lalu_dibagi_porsi(): void
    {
        Bahan::factory()->create([
            'nama' => 'Beras putih',
            'energi_kkal' => 360, 'protein_g' => 10, 'lemak_g' => 0, 'karbohidrat_g' => 0,
        ]);
        Bahan::factory()->create([
            'nama' => 'Minyak goreng',
            'satuan_dasar' => \App\Enums\SatuanDasar::Mililiter,
            'energi_kkal' => 884, 'protein_g' => 0, 'lemak_g' => 100, 'karbohidrat_g' => 0,
            'faktor_densitas' => 1,
        ]);

        $hasil = (new KalkulasiGizi)->hitung([
            ['nama' => 'Beras putih', 'jumlah' => 300, 'satuan' => 'gram'],   // 1080 kkal
            ['nama' => 'Minyak goreng', 'jumlah' => 100, 'satuan' => 'ml'],   //  884 kkal
        ], 4);

        // (1080 + 884) / 4 = 491 kkal per porsi.
        $this->assertEqualsWithDelta(491, $hasil['energi_kkal'], 0.01);
        $this->assertSame(100, $hasil['cakupan']);
        $this->assertSame(2, $hasil['totalBahan']);
    }
}
