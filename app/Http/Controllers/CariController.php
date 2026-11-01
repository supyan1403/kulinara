<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CariController extends Controller
{
    /**
     * Halaman pencarian akun (profil) publik.
     *
     * Dibuka tanpa login — tujuannya menemukan kreator baru, bukan
     * melihat koleksi pribadi. Hanya akun berperan `pengguna` yang
     * muncul; Pengelola disaring karena profil mereka memang diawari
     * ke tim kurasi, bukan publik.
     */
    public function index(Request $request)
    {
        $kata = trim((string) $request->query('akun', ''));
        $authId = $request->user()?->id;

        $query = User::query()
            ->select(['id', 'name', 'username', 'avatar_path', 'verified'])
            ->where('role', 'pengguna')
            ->whereNotNull('username')
            ->where('username', '!=', '')
            ->when($authId, fn ($q) => $q->where('id', '!=', $authId))
            ->withCount('reseps')
            ->when($kata !== '', function ($q) use ($kata) {
                $q->where(function ($sub) use ($kata) {
                    if (config('database.default') === 'pgsql') {
                        $sub->where('name', 'ilike', "%{$kata}%")
                            ->orWhere('username', 'ilike', "%{$kata}%");
                    } else {
                        $sub->where('name', 'like', "%{$kata}%")
                            ->orWhere('username', 'like', "%{$kata}%");
                    }
                });
            }, function ($q) {
                $q->orderByDesc('reseps_count');
            });

        $pengguna = $query->paginate(24)->withQueryString();

        return Inertia::render('Cari/Index', [
            'kataCari' => $kata,
            'pengguna' => $pengguna,
        ]);
    }
}
