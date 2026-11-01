<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sistem like menggantikan follow.
 *
 * Tabel `pengikut` (relasi follow) dihapus karena follow di aplikasi ini tidak
 * pernah punya tujuan: tidak ada feed, tidak ada notifikasi keluar — hanya
 * angka "pengikut" yang tampil. Sebagai gantinya ada dua bentuk apresiasi
 * yang dipakai:
 *
 *   - suka profil  (Suka: 'Profil') — anggukan ke si koki, jadi statistik
 *                  profil dan kontribusi ke skor resep unggulannya
 *   - suka resep   (Suka: 'Resep')  — like masakan, bahan baku skor unggulan
 *
 * Satu tabel polymorphic menampung keduanya: bentuk kolomnya identik, hanya
 * beda target, dan UNIQUE gabungan mencegah like ganda dari satu pengguna.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('pengikut');

        Schema::create('suka', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('sukaable_type', 64);
            $table->unsignedBigInteger('sukaable_id');

            // Anti like ganda — satu pengguna hanya boleh menyukai satu kali
            // per target. Toggle unlike dilakukan dengan menghapus baris ini,
            // jadi kunci unik juga yang menjamin hitungan tidak pernah lebih
            // dari jumlah pengguna unik.
            $table->unique(
                ['user_id', 'sukaable_type', 'sukaable_id'],
                'suka_unik_per_pengguna',
            );

            // Pengurutan/bersih-bersih "like lama milik X" — bukan
            // kunci asing karena targetnya polymorphic.
            $table->index(['sukaable_type', 'sukaable_id'], 'suka_target_idx');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suka');

        // Balikkan tabel follow apa adanya (tanpa isi) — migrasi aslinya
        // tidak ikut dikembalikan karena isi lama tidak mungkin dipulihkan
        // dari sini.
        Schema::create('pengikut', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mengikuti_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'mengikuti_id']);
        });
    }
};
