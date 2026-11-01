<?php

namespace App\Http\Requests;

use App\Enums\StatusResep;
use App\Enums\UserRole;

class UpdateResepRequest extends StoreResepRequest
{
    /**
     * Izinkan pemilik resep atau Pengelola (lihat ResepPolicy).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('resep')) ?? false;
    }

    /**
     * Selain status yang boleh dipilih peran, selalu izinkan status saat ini.
     *
     * Tanpa ini, Pengguna tidak bisa menyimpan resep miliknya sendiri
     * begitu Pengelola menandainya Terverifikasi.
     *
     * @return array<int, string>
     */
    protected function statusDiizinkan(): array
    {
        return StatusResep::diizinkanUntuk(
            $this->user()?->role ?? UserRole::Pengguna,
            $this->route('resep')?->status,
        );
    }

    /**
     * Pesan galat berbahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'status.in' => 'Status tidak valid. Pengguna hanya boleh memilih Draft, '
                .'atau mempertahankan status yang sedang berlaku.',
        ]);
    }
}
