<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel relasi mengikuti/mengikuti: user_id mengikuti mengikuti_id.
     * Unique composite mencegah follow ganda. ON DELETE CASCADE di kedua sisi
     * supaya data bersih saat akun dihapus.
     */
    public function up(): void
    {
        Schema::create('pengikut', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('mengikuti_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'mengikuti_id'], 'pengikut_user_mengikuti_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengikut');
    }
};
