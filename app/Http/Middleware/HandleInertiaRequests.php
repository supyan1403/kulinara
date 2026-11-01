<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user === null ? null : [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'role_label' => $user->role->label(),
                    // Path relatif (images/avatar/…), bukan URL penuh.
                    // Frontend yang menambahkan awalan root lewat `urlAvatar()`
                    // supaya halaman bersarang tidak menghasilkan 404.
                    'avatar_path' => $user->avatar_path,
                    'username' => $user->username,
                    'email_verified_at' => $user->email_verified_at,
                ],
                'permissions' => [
                    'access-studio' => $user?->canAccessStudio() ?? false,
                    'access-panel' => $user?->canAccessPanel() ?? false,
                    'manage-users' => $user?->can('manage-users') ?? false,
                ],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            // Id dua jenis "suka" milik pengunjung yang sedang login.
            // Dibagikan dari sini supaya SATU permintaan Inertia membekali
            // seluruh kartu resep dan kartu penulis dengan keadaan hati
            // (terisi/habis) — tanpa query terpisah per kartu, dan tanpa
            // tiap halaman harus mengirimnya sendiri-sendiri. Tamu dapat
            // daftar kosong: hati semua tampil kosong, belum bukan "sudah".
            'sukaSaya' => [
                'resep' => fn () => $user === null
                    ? []
                    : $user->sukaan()
                        ->where('sukaable_type', \App\Models\Suka::RESEP)
                        ->pluck('sukaable_id')
                        ->map(fn ($id) => (int) $id)
                        ->values(),
                'profil' => fn () => $user === null
                    ? []
                    : $user->sukaan()
                        ->where('sukaable_type', \App\Models\Suka::PROFIL)
                        ->pluck('sukaable_id')
                        ->map(fn ($id) => (int) $id)
                        ->values(),
            ],
            // Tombol "Masuk"/"Daftar" di bilah navigasi dan grup "Akun" di
            // footer tampil hanya bila rutenya benar-benar ada. Dibagikan di
            // sini supaya setiap halaman mendapatkannya: dulu kedua properti
            // ini dikirim dari closure beranda saja, sehingga halaman yang
            // lahir belakangan membuka footer dengan grup "Akun" yang kosong.
            'canLogin' => fn () => Route::has('login'),
            'canRegister' => fn () => Route::has('register'),
        ];
    }
}
