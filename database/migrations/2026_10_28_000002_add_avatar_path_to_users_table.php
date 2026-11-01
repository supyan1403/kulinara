<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Foto profil.
 *
 * Nullable dan tanpa default: tidak ada satu pun akun yang punya foto saat
 * perubahan ini mendarat, dan avatar inisial tetap menjadi wajah bawaan.
 * Foto hanya muncul bagi pengguna yang benar-benar mengunggahnya.
 *
 * Disimpan sebagai path relatif dari folder `public/` — sama seperti kolom
 * `gambar` pada resep — supaya frontend memakai satu aturan URL saja dan
 * berkasnya tidak bergantung pada symlink storage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar_path');
        });
    }
};
