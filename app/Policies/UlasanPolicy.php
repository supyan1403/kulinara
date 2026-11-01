<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Ulasan;
use App\Models\User;

/**
 * Aturan siapa boleh menulis, mengubah, membalas, dan menghapus ulasan.
 *
 * Aturan terpenting di sini adalah larangan mengulas resep sendiri. Tanpa itu
 * penulis resep bisa memberi dirinya bintang lima, dan angka rata-rata di
 * kartu resep — yang jadi pertimbangan orang memilih — kehilangan maknanya.
 * Larangan ini tidak bisa ditegakkan di validasi formulir biasa karena butuh
 * membandingkan dua baris data, jadi tempatnya memang di policy.
 */
class UlasanPolicy
{
    /**
     * Menulis ulasan baru untuk sebuah resep.
     *
     * Semua pengguna yang sudah masuk boleh mengulas, apa pun perannya —
     * Pengelola pun. Menutupnya untuk Pengelola akan menghalangi mereka
     * menguji alur ini sendiri tanpa akun kedua.
     */
    public function create(User $user, int $resepId, int $penulisResepId): bool
    {
        return $user->id !== $penulisResepId;
    }

    /**
     * Mengubah ulasan yang sudah ada — hanya penulisnya.
     */
    public function update(User $user, Ulasan $ulasan): bool
    {
        return $ulasan->user_id === $user->id;
    }

    /**
     * Membalas ulasan — hanya penulis resep yang diulas.
     *
     * Bukan Pengelola: balasan adalah tanggapan personal pemilik resep.
     * Pengelola punya jalur lain (menghapus) untuk menangani ulasan yang
     * bermasalah, dan tidak perlu ikut bicara atas nama penulis resep.
     */
    public function balas(User $user, Ulasan $ulasan): bool
    {
        return $ulasan->resep->user_id === $user->id;
    }

    /**
     * Menghapus ulasan — penulisnya sendiri, atau Pengelola untuk moderasi.
     */
    public function delete(User $user, Ulasan $ulasan): bool
    {
        return $ulasan->user_id === $user->id
            || $user->role === UserRole::Pengelola;
    }
}
