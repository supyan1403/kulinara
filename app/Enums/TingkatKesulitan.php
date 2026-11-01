<?php

namespace App\Enums;

enum TingkatKesulitan: string
{
    case Mudah = 'mudah';
    case Sedang = 'sedang';
    case Sulit = 'sulit';

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
            self::Mudah => 'Mudah',
            self::Sedang => 'Sedang',
            self::Sulit => 'Sulit',
        };
    }

    /**
     * Daftar untuk dikirim ke React: value enum + label tampilan.
     *
     * Dipakai saringan direktori /resep supaya sumber labelnya tetap enum
     * yang sama — bukan ditulis ulang di sisi klien.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function untukProps(): array
    {
        return array_map(
            fn (self $kesulitan) => ['value' => $kesulitan->value, 'label' => $kesulitan->label()],
            self::cases(),
        );
    }
}
