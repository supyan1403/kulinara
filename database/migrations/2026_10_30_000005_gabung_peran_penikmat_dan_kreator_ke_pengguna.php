<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Peran `Penikmat` dan `Kreator` digabung menjadi satu: `Pengguna`.
     *
     * Kolom `role` adalah PostgreSQL enum `user_role`, yang tidak boleh
     * ditambah nilainya di dalam transaksi (migrasi Laravel dibungkus
     * transaksi). Karena itu kolom diubah menjadi `text` supaya nilai `role`
     * bebas berupa string apapun, lalu data lama (`penikmat` dan
     * `chef_kreator`) disamakan ke `pengguna`.
     *
     * Di SQLite (unit test) kolom sudah bertype teks, jadi cukup update data.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users ALTER COLUMN role TYPE text USING role::text');
            DB::statement('DROP TYPE IF EXISTS user_role CASCADE');
        }

        DB::table('users')->whereIn('role', ['penikmat', 'chef_kreator'])->update(['role' => 'pengguna']);
    }

    /**
     * Reverse the migrations.
     *
     * Tidak dapat dikembalikan secara aman karena informasi asli
     * (`penikmat` vs `chef_kreator`) sudah hilang setelah digabung, dan tipe
     * enum `user_role` sudah dihapus. Dikosongkan sengaja.
     */
    public function down(): void
    {
        //
    }
};
