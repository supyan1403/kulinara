<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menghapus fitur buka/tutup Studio Dapur per-pengguna dan menggantinya
     * dengan kolom profil publik agar nama penulis bisa di-klik ke halaman
     * profil seperti Instagram.
     *
     * Kolom yang ditambah: username (unik), bio, lokasi, website.
     * Kolom yang dihapus: dapur_buka (tidak lagi dipakai).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username')->unique()->nullable()->after('name');
            $table->text('bio')->nullable()->after('username');
            $table->string('lokasi')->nullable()->after('bio');
            $table->string('website')->nullable()->after('lokasi');
            $table->dropColumn('dapur_buka');
        });
    }

    /**
     * Tidak dapat dikembalikan secara aman karena data `dapur_buka` dan
     * `username` yang baru diisi akan hilang. Dikosongkan sengaja.
     */
    public function down(): void
    {
        //
    }
};
