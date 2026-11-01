<?php

namespace App\Enums;

/**
 * Kelompok bahan pada tabel rujukan `bahan_pangan`.
 *
 * Berbeda dengan `KategoriResep` (yang mengelompokkan HIDANGAN jadi), enum
 * ini mengelompokkan BAHAN DASAR menurut perannya dalam masakan — dipakai
 * untuk menyaring daftar bahan di Studio Dapur dan di direktori /resep.
 */
enum BahanKategori: string
{
    case Karbohidrat = 'karbohidrat';
    case ProteinHewani = 'protein_hewani';
    case ProteinNabati = 'protein_nabati';
    case Sayur = 'sayur';
    case BumbuRempah = 'bumbu_rempah';
    case LemakMinyak = 'lemak_minyak';
    case Cairan = 'cairan';
    case Lainnya = 'lainnya';

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
            self::Karbohidrat => 'Karbohidrat',
            self::ProteinHewani => 'Protein Hewani',
            self::ProteinNabati => 'Protein Nabati',
            self::Sayur => 'Sayur',
            self::BumbuRempah => 'Bumbu & Rempah',
            self::LemakMinyak => 'Lemak & Minyak',
            self::Cairan => 'Cairan',
            self::Lainnya => 'Lainnya',
        };
    }

    /**
     * Daftar untuk dikirim ke React: value enum + label tampilan.
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
