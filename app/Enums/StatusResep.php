<?php

namespace App\Enums;

enum StatusResep: string
{
    case Draft = 'draft';

    /**
     * Sudah diajukan Kreator dan menunggu tinjauan Pengelola.
     *
     * Status ini sengaja TERPISAH dari Draft. Tanpa ia, Kreator tidak bisa
     * membedakan "belum selesai saya tulis" dari "sedang ditinjau" — dua
     * keadaan yang menuntut tindakan berbeda, dan Pengelola tidak punya
     * antrean yang jelas untuk dikerjakan.
     */
    case Diajukan = 'diajukan';

    case Terbit = 'terbit';
    case Terverifikasi = 'terverifikasi';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Label tampilan berbahasa Indonesia.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Diajukan => 'Menunggu ditinjau',
            self::Terbit => 'Terbit',
            self::Terverifikasi => 'Terverifikasi',
        };
    }

    /**
     * Boleh ditampilkan ke publik di halaman utama?
     *
     * `Diajukan` sengaja TIDAK termasuk: resep yang belum ditinjau belum
     * layak dibaca pengunjung — justru itu inti kurasinya.
     */
    public function isPublic(): bool
    {
        return in_array($this, [self::Terbit, self::Terverifikasi], true);
    }

    /**
     * Status yang boleh dipilih oleh sebuah peran.
     *
     * Kreator hanya boleh memilih Draft. Ia TIDAK boleh menayangkan resepnya
     * sendiri — langkah "terbit" adalah keputusan Pengelola lewat antrean
     * kurasi. Pengelola tetap memegang kendali penuh.
     *
     * Status yang sedang berlaku selalu ikut disertakan agar pemilik resep
     * tetap bisa menyimpan tanpa terpaksa menurunkan statusnya.
     *
     * @return array<int, string>
     */
    public static function diizinkanUntuk(UserRole $role, ?self $saatIni = null): array
    {
        $daftar = $role === UserRole::Pengelola
            ? self::values()
            : [self::Draft->value];

        if ($saatIni !== null && ! in_array($saatIni->value, $daftar, true)) {
            $daftar[] = $saatIni->value;
        }

        return $daftar;
    }

    /**
     * Boleh diajukan ke antrean kurasi?
     */
    public function bisaDiajukan(): bool
    {
        return $this === self::Draft;
    }

    /**
     * Masih menunggu tindakan Pengelola?
     */
    public function menungguKurasi(): bool
    {
        return $this === self::Diajukan;
    }
}
