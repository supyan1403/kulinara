<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * Di PostgreSQL memakai tipe ENUM native (`CREATE TYPE user_role`).
     * Di SQLite (dipakai unit test) jatuh ke kolom enum biasa agar tetap jalan.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('DROP TYPE IF EXISTS user_role');
            DB::statement("CREATE TYPE user_role AS ENUM ('penikmat', 'chef_kreator', 'pengelola')");
            DB::statement("ALTER TABLE users ADD COLUMN role user_role NOT NULL DEFAULT 'penikmat'");
            DB::statement('CREATE INDEX users_role_index ON users (role)');

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', UserRole::values())
                ->default(UserRole::Pengguna->value)
                ->after('password');
        });
    }

    /**
     * Urungkan migrasi.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_role_index');
            });

            DB::statement('ALTER TABLE users DROP COLUMN role');
            DB::statement('DROP TYPE IF EXISTS user_role');

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
