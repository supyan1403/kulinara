<?php

namespace App\Enums;

enum UserRole: string
{
    case Pengguna = 'pengguna';

    case Pengelola = 'pengelola';

    /**
     * Semua value yang valid, untuk dipakai di validasi & migrasi.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Label identitas berbahasa Indonesia.
     *
     * Dipakai Panel Pengelola — daftar pengguna, menu pilihan peran, dan
     * pesan sukses "Role X diperbarui menjadi Y" — jadi bentuknya harus
     * nama peran. Memakai kalimat niat di sini akan terbaca janggal.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pengguna => 'Pengguna',
            self::Pengelola => 'Pengelola',
        };
    }

    /**
     * Label berbentuk NIAT — hanya untuk kartu pilihan di halaman daftar.
     *
     * Kartu itu ditujukan kepada orang yang belum melakukan apa pun.
     * Bertanya "siapa Anda?" memaksa ia mengklaim status yang belum tentu
     * benar, jadi menyebut tindakan ("Saya mau berbagi resep") jujur, tidak
     * menuntut kredensial apa pun.
     */
    public function intentLabel(): string
    {
        return match ($this) {
            self::Pengguna => 'Menikmati resep',
            self::Pengelola => 'Pengelola',
        };
    }

    /**
     * Kelas Tailwind untuk lencana berbentuk pill (rounded-full).
     *
     * Saat ini tidak dipanggil dari mana pun — lencana frontend memakai
     * peta warnanya sendiri di Components/RoleBadge.jsx. Dipertahankan
     * supaya pemetaan warnanya tidak hilang bila kelak dipakai.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Pengguna => 'bg-linen text-charcoal ring-border',
            self::Pengelola => 'bg-emerald-100 text-emerald-800 ring-emerald-600/20',
        };
    }

    /**
     * Apakah role ini punya akses ke Studio Dapur.
     *
     * Semua yang login (Pengguna maupun Pengelola) boleh masuk Studio.
     * Fitur buka/tutup dapur per-pengguna sudah dihapus; akses ditentukan
     * oleh role saja.
     */
    public function canAccessStudio(): bool
    {
        return true;
    }

    /**
     * Apakah role ini punya akses ke Panel Pengelola.
     */
    public function canAccessPanel(): bool
    {
        return $this === self::Pengelola;
    }
}
