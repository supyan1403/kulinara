<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Pengaturan dapur disimpan di tabel `users` agar satu baris per pengguna
     * dan tanpa model terpisah. Semua kolom nullable di sini — kode di sisi
     * aplikasi memperlakukan NULL sebagai "nyala/default".
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('dapur_buka')->default(true)->after('role');
            $table->boolean('notif_rating')->default(true)->after('dapur_buka');
            $table->boolean('notif_ulasan')->default(true)->after('notif_rating');
            $table->boolean('notif_kurasi')->default(true)->after('notif_ulasan');
            $table->unsignedTinyInteger('porsi_default')->default(4)->after('notif_kurasi');
            $table->string('satuan_default', 20)->default('gram')->after('porsi_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'dapur_buka',
                'notif_rating',
                'notif_ulasan',
                'notif_kurasi',
                'porsi_default',
                'satuan_default',
            ]);
        });
    }
};
