<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tandai resep sebagai "unggulan" (pinned) di profil penulis, mirip
     * highlight Instagram. Hanya resep yang sudah terbit yang boleh diunggulkan.
     */
    public function up(): void
    {
        Schema::table('resep', function (Blueprint $table): void {
            $table->boolean('unggulan')->default(false)->after('status');
            $table->integer('urutan_unggulan')->default(0)->after('unggulan');
        });
    }

    public function down(): void
    {
        Schema::table('resep', function (Blueprint $table): void {
            $table->dropColumn(['unggulan', 'urutan_unggulan']);
        });
    }
};
