<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah foto sampul (cover) dan tanda verifikasi pada tabel users
     * agar profil publik mirip Instagram: banner di atas, badge verifikasi
     * di samping nama.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('foto_sampul')->nullable()->after('avatar_path');
            $table->boolean('verified')->default(false)->after('foto_sampul');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['foto_sampul', 'verified']);
        });
    }
};
