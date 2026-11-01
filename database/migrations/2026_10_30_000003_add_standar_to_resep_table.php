<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom standarisasi ke tabel resep.
     *
     * Empat kolom ini mengubah resep dari "catatan masak" menjadi "dokumen
     * yang bisa direproduksi" — tulang punggung kerja teknologi pangan:
     *
     *  - `hasil_total`  — berat/ volume hasil akhir setelah dimasak.
     *                     Tanpanya tak ada yang tahu apakah masakan berhasil
     *                     menghasilkan takaran yang dijanjikan.
     *  - `satuan_hasil` — gram atau mililiter, pasangan `hasil_total`.
     *  - `alat`         — peralatan yang dipakai. Bagian dari SOP: hasil
     *                     masak dengan blender berbeda dari yang diulek.
     *  - `gizi_cakupan` — persentase bahan yang benar-benar masuk hitungan
     *                     gizi otomatis. Disimpan (bukan dihitung ulang)
     *                     supaya badge "Standar" bisa diputuskan tanpa
     *                     membaca ulang seluruh daftar bahan.
     *
     * Semua kolom nullable/bernilai nol — resep lama tidak kehilangan
     * apa pun, dan standarisasi bertumbuh seiring penulis mengisinya.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement("DROP TYPE IF EXISTS satuan_hasil");
            DB::statement("CREATE TYPE satuan_hasil AS ENUM ('gram', 'ml')");
            DB::statement('ALTER TABLE resep ADD COLUMN hasil_total NUMERIC(10,2)');
            DB::statement("ALTER TABLE resep ADD COLUMN satuan_hasil satuan_hasil");
            DB::statement('ALTER TABLE resep ADD COLUMN alat JSONB');
            DB::statement('ALTER TABLE resep ADD COLUMN gizi_cakupan INTEGER NOT NULL DEFAULT 0');
            DB::statement("ALTER TABLE resep ADD CONSTRAINT resep_hasil_positif CHECK (hasil_total IS NULL OR hasil_total > 0)");

            return;
        }

        Schema::table('resep', function (Blueprint $table) {
            $table->decimal('hasil_total', 10, 2)->nullable();
            $table->enum('satuan_hasil', ['gram', 'ml'])->nullable();
            $table->json('alat')->nullable();
            $table->unsignedInteger('gizi_cakupan')->default(0);
        });
    }

    /**
     * Urungkan migrasi.
     */
    public function down(): void
    {
        Schema::table('resep', function (Blueprint $table) {
            $table->dropColumn(['hasil_total', 'satuan_hasil', 'alat', 'gizi_cakupan']);
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('DROP TYPE IF EXISTS satuan_hasil');
        }
    }
};
