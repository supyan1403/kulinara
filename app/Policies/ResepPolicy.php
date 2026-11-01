<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Resep;
use App\Models\User;

class ResepPolicy
{
    /**
     * Siapa yang boleh membuka Studio Dapur — sudah dijaga middleware `role:`.
     */
    public function viewAny(User $user): bool
    {
        return $user->role->canAccessStudio();
    }

    /**
     * Buat resep baru.
     */
    public function create(User $user): bool
    {
        return $user->role->canAccessStudio();
    }

    /**
     * Ubah resep: pemiliknya sendiri, atau Pengelola (moderasi).
     */
    public function update(User $user, Resep $resep): bool
    {
        return $this->pengelolaAtauPemilik($user, $resep);
    }

    /**
     * Hapus resep: pemiliknya sendiri, atau Pengelola.
     */
    public function delete(User $user, Resep $resep): bool
    {
        return $this->pengelolaAtauPemilik($user, $resep);
    }

    /**
     * Tandai terverifikasi — kewenangan mutlak Pengelola.
     */
    public function verify(User $user, Resep $resep): bool
    {
        return $user->role === UserRole::Pengelola;
    }

    private function pengelolaAtauPemilik(User $user, Resep $resep): bool
    {
        return $user->role === UserRole::Pengelola
            || $resep->user_id === $user->id;
    }
}
