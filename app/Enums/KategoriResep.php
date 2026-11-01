<?php

namespace App\Enums;

enum KategoriResep: string
{
    case DagingSapi = 'daging_sapi';
    case AyamUnggas = 'ayam_unggas';
    case IkanSeafood = 'ikan_seafood';
    case SayurNabati = 'sayur_nabati';
    case NasiKarbo = 'nasi_karbo';
    case Jajanan = 'jajanan';

    /**
     * Semua value yang valid, untuk validasi & migrasi.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Label tampilan berbahasa Indonesia — dipakai di chip kategori.
     */
    public function label(): string
    {
        return match ($this) {
            self::DagingSapi => 'Daging Sapi',
            self::AyamUnggas => 'Ayam & Unggas',
            self::IkanSeafood => 'Ikan & Seafood',
            self::SayurNabati => 'Sayur & Nabati',
            self::NasiKarbo => 'Nasi & Karbohidrat',
            self::Jajanan => 'Jajanan Pasar',
        };
    }

    /**
     * Daftar kategori untuk dikirim ke React: value enum + label tampilan.
     *
     * Dibutuhkan di dua halaman (landing dan /resep), jadi disusun sekali
     * di sini — bukan dua kali di dalam dua controller.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function untukProps(): array
    {
        return array_map(
            fn (self $kategori) => ['value' => $kategori->value, 'label' => $kategori->label()],
            self::cases(),
        );
    }
}
