<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel ulasan — satu baris = satu penilaian pengguna atas satu resep.
     *
     * `rating` wajib, `komentar` opsional: orang yang hanya ingin menekan
     * bintang tidak dipaksa menulis kalimat. Kolomnya tidak boleh null pada
     * rating karena nilai rata-rata tidak bisa dihitung dari baris kosong.
     *
     * `unique(['resep_id', 'user_id'])` dijaga di tingkat basis data, bukan
     * hanya di aplikasi. Tanpa ini, dua klik cepat pada tombol kirim bisa
     * menghasilkan dua baris dari orang yang sama, dan rata-rata jadi berat
     * sebelah tanpa ada yang menyadarinya. Dengan unique index, percobaan
     * kedua gagal di basis data — bukan diam-diam diterima.
     *
     * `balasan` dan `balasan_at` hidup di tabel yang sama, bukan tabel
     * terpisah: balasan hanya ada satu per ulasan dan selalu milik penulis
     * resepnya. Tabel sendiri akan menambah satu relasi dan satu query tanpa
     * membuka kemungkinan yang tidak dimiliki bentuk kolom ini.
     *
     * `cascadeOnDelete` di kedua sisi supaya tidak tersisa baris yatim.
     */
    public function up(): void
    {
        Schema::create('ulasan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resep_id')->constrained('resep')->cascadeOnDelete();

            $table->unsignedTinyInteger('rating');
            $table->text('komentar')->nullable();

            $table->text('balasan')->nullable();
            $table->timestamp('balasan_at')->nullable();

            $table->timestamps();

            $table->unique(['resep_id', 'user_id']);
            $table->index('resep_id');
        });
    }

    /**
     * Urungkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('ulasan');
    }
};
