<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Isi `username` untuk seluruh user yang sudah ada (data dummy dan demo)
     * dari nama mereka, sehingga nama penulis di tiap resep bisa di-klik ke
     * `/pengguna/{username}` untuk semua pengguna, bukan hanya satu akun.
     *
     * Username dibangun lewat Str::slug dari `name`; bentrok diberi imbuhan
     * -2, -3, dst. User yang sudah punya username tidak ditimpa.
     */
    public function up(): void
    {
        $users = DB::table('users')
            ->whereNull('username')
            ->orWhere('username', '')
            ->orderBy('id')
            ->get();

        $digunakan = [];
        foreach (DB::table('users')->whereNotNull('username')->where('username', '!=', '')->pluck('username') as $u) {
            $digunakan[$u] = true;
        }

        foreach ($users as $user) {
            $dasar = Str::slug($user->name) ?: 'pengguna';
            $username = $dasar;
            $n = 1;
            while (isset($digunakan[$username])) {
                $username = $dasar . '-' . ++$n;
            }
            $digunakan[$username] = true;

            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        }
    }

    /**
     * Data username yang dihasilkan tidak bisa dikembalikan dengan aman.
     * Dikosongkan sengaja.
     */
    public function down(): void
    {
        //
    }
};
