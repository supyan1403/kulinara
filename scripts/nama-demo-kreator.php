<?php

/**
 * Baca + perbaiki nama akun demo peran Kreator.
 *
 * RoleSeeder memakai firstOrCreate, jadi mengganti namanya di seeder tidak
 * menyentuh baris yang sudah ada di database dev. Skrip ini melaporkan
 * apakah nama lama masih dipakai (dan apakah akunnya menulis resep yang
 * tampil ke publik), lalu memperbaruinya.
 */

use App\Models\Resep;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

// Impor diletakkan di atas seluruh kode, bukan di bawah `require`. Pint
// memindahkannya ke bawah bila mereka ditulis setelah baris berjalan, dan
// alias yang baru terbaca membuat `Kernel::class` jatuh menjadi string
// literal "Kernel" — bootstrap lalu gagal.
require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

$tua = User::query()->where('email', 'chef@kulinara.test')->first();

if (! $tua) {
    echo "Tidak ada akun chef@kulinara.test — tidak ada yang diperbaiki.\n";

    return;
}

$resep = Resep::query()->where('user_id', $tua->id)->count();

echo "Nama lama : {$tua->name}\n";
echo "Peran     : {$tua->role->value}\n";
echo "Resep     : {$resep}\n";

if ($tua->name !== 'Budi Kreator') {
    $tua->update(['name' => 'Budi Kreator']);
    echo "Diperbarui menjadi: Budi Kreator\n";
} else {
    echo "Sudah benar, tidak diubah.\n";
}

// Akun demo lain yang mungkin memuat kata "Chef" di namanya.
$sisa = User::query()
    ->where('name', 'like', '%Chef%')
    ->get(['id', 'name', 'email']);

echo 'Akun lain masih memuat "Chef": '.$sisa->count()."\n";

foreach ($sisa as $u) {
    echo "  - {$u->name} <{$u->email}> (".Resep::query()->where('user_id', $u->id)->count()." resep)\n";
}
