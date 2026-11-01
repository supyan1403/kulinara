<?php

/**
 * Hapus akun uji yang dibuat tmp/uji-naik-pangkat.mjs.
 *
 * Alur uji mendaftar lewat formulir sungguhan, jadi ia meninggalkan baris
 * di database pengembangan. Akun demo (pengguna@, pengelola@) tidak
 * pernah disentuh oleh uji itu dan karenanya tidak pernah disentuh di sini
 * — penghapusannya dikunci pada pola surel uji.
 */

use App\Models\Resep;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

// Impor diletakkan di atas seluruh kode. Pint memindahkannya ke bawah bila
// mereka ditulis setelah `require`, dan alias yang baru terbaca setelah
// baris pemakaiannya membuat `Kernel::class` terbaca sebagai string literal
// "Kernel" — lalu bootstrap gagal dengan "Target class [Kernel] does not
// exist".
require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

$pola = 'naik-pangkat-%@uji.test';

$korban = User::query()->where('email', 'like', $pola)->get(['id', 'name', 'email', 'role']);

echo 'Ditemukan: '.$korban->count()."\n";

foreach ($korban as $u) {
    $resep = Resep::query()->where('user_id', $u->id)->count();

    // Aman dihapus: akun uji tidak pernah sempat menulis resep. Kalau suatu
    // hari ada, hentikan alih-alih menghapus karyanya diam-diam.
    if ($resep > 0) {
        echo "  LEWAT  {$u->email} — punya {$resep} resep, tidak dihapus.\n";

        continue;
    }

    $u->delete();
    echo "  HAPUS  {$u->email} (peran: {$u->role->value})\n";
}

$sisa = User::query()->where('email', 'like', $pola)->count();
echo "Sisa akun uji: {$sisa}\n";
echo 'Total pengguna: '.User::query()->count()."\n";
