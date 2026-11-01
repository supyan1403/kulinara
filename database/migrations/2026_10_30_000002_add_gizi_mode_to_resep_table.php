<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom `gizi_mode` ke tabel resep.
     *
     * Menyimpan apakah nilai gizi resep ini dihitung OTOMATIS dari daftar
     * bahan atau diisi MANUAL oleh penulis. Nilai default untuk baris lama
     * adalah `manual` — karena sebelum kolom ini ada, seluruh gizi memang
     * diisi tangan, dan menganggapnya otomatis akan menampilkan angka lama
     * seolah-olah dihitung dari bahan (yang bisa jadi tidak benar).
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement("DROP TYPE IF EXISTS gizi_mode");
            DB::statement("CREATE TYPE gizi_mode AS ENUM ('otomatis', 'manual')");
            DB::statement('ALTER TABLE resep ADD COLUMN gizi_mode gizi_mode NOT NULL DEFAULT \'manual\'');

            return;
        }

        Schema::table('resep', function (Blueprint $table) {
            $table->enum('gizi_mode', ['otomatis', 'manual'])->default('manual');
        });
    }

    /**
     * Urungkan migrasi.
     */
    public function down(): void
    {
        Schema::table('resep', function (Blueprint $table) {
            $table->dropColumn('gizi_mode');
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('DROP TYPE IF EXISTS gizi_mode');
        }
    }
};
