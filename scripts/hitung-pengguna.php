<?php

/**
 * Read-only: jumlah pengguna di database pengembangan.
 *
 * Dipakai untuk memastikan pemeriksaan browser tidak meninggalkan akun
 * sampah. Seharusnya selalu 202 — jumlah benih. Naik berarti ada uji yang
 * mengirim formulir pendaftaran.
 *
 *   php scripts/hitung-pengguna.php
 */

use App\Models\Resep;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

printf("database : %s / %s\n", DB::connection()->getDriverName(), DB::getDatabaseName());
printf("pengguna : %d\n", DB::table('users')->count());
printf("resep    : %d\n", Resep::count());

$nyasar = DB::table('users')->where('email', 'like', '%@uji.test')->count();
printf("akun uji : %d\n", $nyasar);

if ($nyasar > 0) {
    DB::table('users')->where('email', 'like', '%@uji.test')->delete();
    printf("→ %d akun uji dibersihkan\n", $nyasar);
}
