<?php

use App\Enums\KategoriResep;
use App\Enums\StatusResep;
use App\Enums\TingkatKesulitan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel resep.
     *
     * Jalur PostgreSQL memakai fitur native:
     *  - tipe ENUM (`kategori_resep`, `tingkat_kesulitan`, `status_resep`)
     *  - kolom `jsonb` untuk bahan & langkah (bisa dicari per elemen lewat GIN)
     *  - kolom generated tersimpan `pencarian` berupa tsvector (full-text search)
     *  - CHECK constraint untuk menjaga integritas angka & bentuk jsonb
     *  - indeks parsial untuk feed publik (mengabaikan draft)
     *
     * SQLite (dipakai unit test) jatuh ke kolom enum/json biasa agar tetap jalan.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            $this->createTypes();
            DB::statement($this->pgsqlCreateTable());
            $this->createIndexes();

            return;
        }

        Schema::create('resep', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('judul');
            $table->string('slug')->unique();
            $table->text('deskripsi');
            $table->string('gambar')->nullable();
            $table->string('daerah', 120)->nullable();
            $table->enum('kategori', KategoriResep::values());
            $table->enum('tingkat_kesulitan', TingkatKesulitan::values())->default('sedang');
            $table->enum('status', StatusResep::values())->default('draft');
            $table->unsignedInteger('waktu_masak_menit')->default(30);
            $table->unsignedInteger('porsi')->default(1);
            $table->decimal('energi_kkal', 8, 2)->default(0);
            $table->decimal('protein_g', 8, 2)->default(0);
            $table->decimal('lemak_g', 8, 2)->default(0);
            $table->decimal('karbohidrat_g', 8, 2)->default(0);
            $table->json('bahan')->nullable();
            $table->json('langkah')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('kategori');
            $table->index('user_id');
        });
    }

    /**
     * Urungkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('resep');

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('DROP TYPE IF EXISTS kategori_resep');
            DB::statement('DROP TYPE IF EXISTS tingkat_kesulitan');
            DB::statement('DROP TYPE IF EXISTS status_resep');
        }
    }

    /**
     * Buat tipe ENUM khas PostgreSQL.
     */
    private function createTypes(): void
    {
        DB::statement('DROP TYPE IF EXISTS kategori_resep');
        DB::statement("CREATE TYPE kategori_resep AS ENUM ('".implode("','", KategoriResep::values())."')");

        DB::statement('DROP TYPE IF EXISTS tingkat_kesulitan');
        DB::statement("CREATE TYPE tingkat_kesulitan AS ENUM ('".implode("','", TingkatKesulitan::values())."')");

        DB::statement('DROP TYPE IF EXISTS status_resep');
        DB::statement("CREATE TYPE status_resep AS ENUM ('".implode("','", StatusResep::values())."')");
    }

    /**
     * Definisi tabel lengkap untuk PostgreSQL.
     */
    private function pgsqlCreateTable(): string
    {
        return <<<'SQL'
            CREATE TABLE resep (
                id                BIGSERIAL PRIMARY KEY,
                user_id           BIGINT NOT NULL REFERENCES users (id) ON DELETE CASCADE,
                judul             VARCHAR(255) NOT NULL,
                slug              VARCHAR(255) NOT NULL UNIQUE,
                deskripsi         TEXT NOT NULL,
                gambar            VARCHAR(255),
                daerah            VARCHAR(120),
                kategori          kategori_resep NOT NULL,
                tingkat_kesulitan tingkat_kesulitan NOT NULL DEFAULT 'sedang',
                status            status_resep NOT NULL DEFAULT 'draft',
                waktu_masak_menit INTEGER NOT NULL DEFAULT 30,
                porsi             INTEGER NOT NULL DEFAULT 1,
                energi_kkal       NUMERIC(8,2) NOT NULL DEFAULT 0,
                protein_g         NUMERIC(8,2) NOT NULL DEFAULT 0,
                lemak_g           NUMERIC(8,2) NOT NULL DEFAULT 0,
                karbohidrat_g     NUMERIC(8,2) NOT NULL DEFAULT 0,
                bahan             JSONB NOT NULL DEFAULT '[]'::jsonb,
                langkah           JSONB NOT NULL DEFAULT '[]'::jsonb,
                pencarian         TSVECTOR GENERATED ALWAYS AS (
                                      to_tsvector('simple', judul || ' ' || deskripsi)
                                  ) STORED,
                created_at        TIMESTAMP,
                updated_at        TIMESTAMP,
                CONSTRAINT resep_porsi_minimal    CHECK (porsi >= 1),
                CONSTRAINT resep_waktu_positif    CHECK (waktu_masak_menit > 0),
                CONSTRAINT resep_energi_nol_negatif CHECK (energi_kkal >= 0),
                CONSTRAINT resep_bahan_array      CHECK (jsonb_typeof (bahan) = 'array'),
                CONSTRAINT resep_langkah_array    CHECK (jsonb_typeof (langkah) = 'array')
            );
            SQL;
    }

    /**
     * Indeks khas PostgreSQL — pencarian penuh, jsonb, dan feed publik.
     */
    private function createIndexes(): void
    {
        DB::statement('CREATE INDEX resep_pencarian_idx ON resep USING GIN (pencarian)');
        DB::statement('CREATE INDEX resep_bahan_idx ON resep USING GIN (bahan)');
        DB::statement('CREATE INDEX resep_kategori_idx ON resep (kategori)');
        DB::statement('CREATE INDEX resep_status_idx ON resep (status)');
        DB::statement('CREATE INDEX resep_user_idx ON resep (user_id)');

        // Feed publik: hanya resep non-draft, terbaru lebih dulu.
        DB::statement("CREATE INDEX resep_feed_idx ON resep (created_at DESC) WHERE status <> 'draft'");
    }
};
