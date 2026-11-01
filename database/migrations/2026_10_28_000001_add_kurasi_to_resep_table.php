<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak kurasi resep.
 *
 * Alurnya mengikuti sistem artikel: Kreator menulis draft, mengajukannya,
 * lalu Pengelola menyetujui atau menolak. Tanpa kolom-kolom ini, panel kurasi
 * tidak bisa menunjukkan kapan sesuatu diajukan, siapa yang meninjau, atau
 * mengapa sebuah resep ditolak — dan penulis yang ditolak tidak punya cara
 * mengetahui apa yang salah.
 *
 * Resep lama (368 baris dummy) sengaja TIDAK disentuh: kolom-kolom ini
 * nullable dan statusnya dibiarkan `terbit`. Aturan kurasi hanya berlaku
 * untuk resep yang dibuat setelah perubahan ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resep', function (Blueprint $table) {
            // Kapan Kreator menekan "Ajukan". Null berarti belum pernah.
            $table->timestamp('diajukan_at')->nullable()->after('status');

            // Kapan Pengelola memutuskan, dan siapa. Null berarti belum ditinjau.
            $table->timestamp('ditinjau_at')->nullable()->after('diajukan_at');
            $table->foreignId('ditinjau_oleh')->nullable()->after('ditinjau_at')
                ->constrained('users')->nullOnDelete();

            // Alasan penolakan. Wajib diisi saat menolak, dikosongkan saat
            // menyetujui agar catatan lama tidak nyangkut di resep yang sudah
            // lolos.
            $table->text('catatan_kurasi')->nullable()->after('ditinjau_oleh');

            // Kapan resep yang SUDAH tayang disunting. Dipakai panel kurasi
            // untuk menandai "disunting setelah terbit" — suntingan tetap
            // berlaku langsung, tapi Pengelola tidak boleh buta terhadapnya.
            $table->timestamp('disunting_setelah_terbit_at')->nullable()->after('catatan_kurasi');
        });
    }

    public function down(): void
    {
        Schema::table('resep', function (Blueprint $table) {
            $table->dropForeign(['ditinjau_oleh']);
            $table->dropColumn([
                'diajukan_at',
                'ditinjau_at',
                'ditinjau_oleh',
                'catatan_kurasi',
                'disunting_setelah_terbit_at',
            ]);
        });
    }
};
