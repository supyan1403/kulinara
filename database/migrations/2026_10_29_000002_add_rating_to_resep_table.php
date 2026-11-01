<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom ringkasan rating di tabel resep.
     *
     * Nilainya dihitung ulang oleh observer `Ulasan`, bukan diambil dengan
     * `withAvg()` setiap kali daftar resep dibuka. Alasannya: direktori
     * menampilkan belasan kartu sekaligus, dan setiap kartu butuh angka ini —
     * satu agregat per kartu berarti belasan subquery pada satu pemuatan
     * halaman. Menyimpannya jadi dua kolom membuat kartu cukup membaca nilai
     * yang sudah ada.
     *
     * Risikonya nilai bisa basi kalau ada jalur tulis yang melewatkan
     * observer. Karena itu `php artisan rating:hitung-ulang` disediakan untuk
     * memulihkannya, dan seluruh penulisan rating hanya boleh lewat model.
     *
     * `default(0)` supaya resep lama (dan resep tanpa ulasan) tidak perlu
     * penanganan khusus: rata-rata 0 dengan jumlah 0 sudah berarti "belum
     * ada ulasan", dan halaman yang menampilkan bisa memakai `jumlah_ulasan`
     * sebagai penentu, bukan `rata_rating`.
     */
    public function up(): void
    {
        Schema::table('resep', function (Blueprint $table) {
            $table->decimal('rata_rating', 3, 2)->default(0)->after('disunting_setelah_terbit_at');
            $table->unsignedInteger('jumlah_ulasan')->default(0)->after('rata_rating');

            // Diindeks karena sortir "rating tertinggi" akan memakainya sebagai
            // kunci utama urutan, dan kolom tanpa indeks memaksa seq scan.
            $table->index('rata_rating');
        });
    }

    /**
     * Urungkan migrasi.
     */
    public function down(): void
    {
        Schema::table('resep', function (Blueprint $table) {
            $table->dropIndex(['rata_rating']);
            $table->dropColumn(['rata_rating', 'jumlah_ulasan']);
        });
    }
};
