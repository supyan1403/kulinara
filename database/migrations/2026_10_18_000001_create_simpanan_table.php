<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel simpanan — penanda "Simpan resep" milik tiap pengguna.
     *
     * Sengaja berupa tabel pemetaan many-to-many, bukan kolom boolean di
     * tabel `resep`: satu resep bisa disimpan banyak orang, dan satu orang
     * bisa menyimpan banyak resep. Unique index ganda mencegah seseorang
     * menyimpan resep yang sama dua kali tanpa perlu pemeriksaan di aplikasi.
     *
     * `cascadeOnDelete` di kedua sisi supaya tidak pernah tersisa baris yatim:
     * menghapus pengguna (misalnya lewat penghapusan akun di Profile) atau
     * menghapus resep ikut membersihkan baris simpanannya.
     *
     * `resep_id` harus menyebut nama tabel eksplisit lewat `constrained('resep')`
     * — Laravel menebak "reseps" bila namanya tidak diberikan.
     */
    public function up(): void
    {
        Schema::create('simpanan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resep_id')->constrained('resep')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'resep_id']);
            $table->index('resep_id');
        });
    }

    /**
     * Urungkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('simpanan');
    }
};
