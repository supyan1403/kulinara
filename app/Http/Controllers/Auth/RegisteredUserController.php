<?php

namespace App\Http\Controllers\Auth;

use App\Enums\KategoriResep;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRegistrationRequest;
use App\Models\Resep;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            // Kartu peran sudah tidak ada — pendaftaran selalu menghasilkan
            // Pengguna. Prop `roles` tidak dikirim lagi.
            //
            // Dihitung langsung, bukan ditulis mati di JSX. Scope `terbit`
            // adalah sumber kebenaran yang sama dengan beranda dan /resep,
            // sehingga angka di panel tidak mungkin berbeda dari yang bisa
            // dilihat pengunjung.
            //
            // Angka kedua tadinya "daerah", dan itu tidak bisa dipertahankan:
            // hanya 173 dari 368 resep terbit yang mengisinya, sehingga
            // "26 daerah" mengiklankan cakupan yang tidak bisa dilihat
            // pengguna di mana pun — chipnya sudah dihapus dari kartu resep,
            // halaman detail, dan pratinjau Studio.
            //
            // Kategori dihitung dari enum, bukan dari tabel, jadi angkanya
            // tidak mungkin berbeda dari daftar filter di beranda dan /resep.
            //
            // Jumlah yang belum tayang sengaja tidak ditampilkan. Resep total
            // dikurangi resep tayang membocorkan berapa banyak yang masih
            // draf, dan "tayang" sendiri adalah bahasa internal.
            'statistik' => [
                ['angka' => Resep::terbit()->count(), 'label' => 'resep'],
                [
                    'angka' => count(KategoriResep::cases()),
                    'label' => 'kategori',
                ],
            ],
        ]);
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(StoreRegistrationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            // Pendaftaran selalu Pengguna. Pilihan peran dihapus dari
            // formulir: keputusan saat mendaftar tidak pernah menentukan
            // apa pun. Pengelola tetap hanya lewat Panel Pengelola.
            'role' => UserRole::Pengguna,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
