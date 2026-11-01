<?php

namespace App\Enums;

/**
 * Satuan dasar penyimpanan nilai gizi pada tabel `bahan_pangan`.
 *
 * Nilai gizi selalu disimpan per 100 mililiter atau per 100 gram — jadi
 * satuan ini menentukan apakah kolom `faktor_densitas` dipakai saat
 * mengubah takaran cairan menjadi berat.
 */
enum SatuanDasar: string
{
    case Gram = 'gram';
    case Mililiter = 'ml';

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
            self::Gram => 'gram',
            self::Mililiter => 'mililiter',
        };
    }
}
