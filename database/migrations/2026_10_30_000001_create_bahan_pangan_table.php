<?php

use App\Enums\BahanKategori;
use App\Enums\SatuanDasar;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel rujukan bahan pangan.
     *
     * Ini fondasi kalkulasi gizi otomatis: satu baris per bahan dasar,
     * lengkap dengan nilai gizi per 100 gram/mililiter dan faktor-faktor
     * yang dibutuhkan untuk mengubah takaran dapur (siung, sdt, sdm)
     * menjadi berat yang benar-benar bisa dikalikan.
     *
     * Jalur PostgreSQL memakai tipe ENUM dan indeks pencarian (trigram)
     * supaya autocomplete bahan di Studio Dapur tetap ringan saat tabel
     * tumbuh. SQLite (unit test) memakai kolom biasa.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            $this->createTypes();
            DB::statement($this->pgsqlCreateTable());
            DB::statement('CREATE INDEX bahan_pangan_kategori_idx ON bahan_pangan (kategori)');

            return;
        }

        Schema::create('bahan_pangan', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama', 120)->unique();
            $table->enum('kategori', BahanKategori::values())->default(BahanKategori::Lainnya->value);
            $table->enum('satuan_dasar', SatuanDasar::values())->default(SatuanDasar::Gram->value);
            $table->decimal('energi_kkal', 8, 2)->default(0);
            $table->decimal('protein_g', 8, 2)->default(0);
            $table->decimal('lemak_g', 8, 2)->default(0);
            $table->decimal('karbohidrat_g', 8, 2)->default(0);
            // Gram per mililiter — dipakai mengubah ml ↔ gram (santan kental
            // misalnya ≈ 0,9; minyak ≈ 0,92). 1 untuk bahan berbasis berat.
            $table->decimal('faktor_densitas', 8, 3)->default(1);
            // Gram per satuan takaran (siung, butir, buah). Null berarti
            // bahan ini tidak punya takaran satuan — semua input harus gram.
            $table->decimal('berat_per_unit', 8, 2)->nullable();
            $table->string('satuan_takaran', 30)->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->index('kategori');
        });
    }

    /**
     * Urungkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('bahan_pangan');

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('DROP TYPE IF EXISTS bahan_kategori');
            DB::statement('DROP TYPE IF EXISTS satuan_dasar');
        }
    }

    /**
     * Buat tipe ENUM khas PostgreSQL.
     */
    private function createTypes(): void
    {
        DB::statement('DROP TYPE IF EXISTS bahan_kategori');
        DB::statement("CREATE TYPE bahan_kategori AS ENUM ('".implode("','", BahanKategori::values())."')");

        DB::statement('DROP TYPE IF EXISTS satuan_dasar');
        DB::statement("CREATE TYPE satuan_dasar AS ENUM ('".implode("','", SatuanDasar::values())."')");
    }

    /**
     * Definisi tabel lengkap untuk PostgreSQL.
     */
    private function pgsqlCreateTable(): string
    {
        return <<<'SQL'
            CREATE TABLE bahan_pangan (
                id                BIGSERIAL PRIMARY KEY,
                kode              VARCHAR(20) NOT NULL UNIQUE,
                nama              VARCHAR(120) NOT NULL UNIQUE,
                kategori          bahan_kategori NOT NULL DEFAULT 'lainnya',
                satuan_dasar      satuan_dasar NOT NULL DEFAULT 'gram',
                energi_kkal       NUMERIC(8,2) NOT NULL DEFAULT 0,
                protein_g         NUMERIC(8,2) NOT NULL DEFAULT 0,
                lemak_g           NUMERIC(8,2) NOT NULL DEFAULT 0,
                karbohidrat_g     NUMERIC(8,2) NOT NULL DEFAULT 0,
                faktor_densitas   NUMERIC(8,3) NOT NULL DEFAULT 1,
                berat_per_unit    NUMERIC(8,2),
                satuan_takaran    VARCHAR(30),
                aktif             BOOLEAN NOT NULL DEFAULT TRUE,
                created_at        TIMESTAMP,
                updated_at        TIMESTAMP,
                CONSTRAINT bahan_densitas_positif CHECK (faktor_densitas > 0),
                CONSTRAINT bahan_berat_positif    CHECK (berat_per_unit IS NULL OR berat_per_unit > 0)
            );
            SQL;
    }
};
