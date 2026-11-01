<?php

/**
 * Read-only: seberapa kaya kolom `daerah` pada resep terbit.
 *
 *   php scripts/daerah-resep.php
 *
 * Menjawab tiga hal: berapa resep terbit yang punya daerah, berapa daerah
 * berbeda, dan bagaimana sebarannya. Kalau kebanyakan resep kosong atau satu
 * daerah mendominasi, chip daerah di kartu tidak akan bekerja.
 */

use App\Models\Resep;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

$table = (new Resep)->getTable();

$total = DB::table($table)->count();
$terbit = Resep::terbit()->count();
$berdaerah = Resep::terbit()
    ->whereNotNull('daerah')
    ->where('daerah', '!=', '')
    ->count();

printf("total resep        : %d\n", $total);
printf("resep terbit       : %d\n", $terbit);
printf("terbit + punya     : %d  (%.0f%%)\n", $berdaerah, $terbit > 0 ? $berdaerah / $terbit * 100 : 0);
printf("daerah berbeda     : %d\n", Resep::terbit()->distinct()->count('daerah'));

echo "\nSepuluh daerah terbanyak:\n";

DB::table($table)
    ->whereIn('status', ['terbit', 'terverifikasi'])
    ->whereNotNull('daerah')
    ->where('daerah', '!=', '')
    ->selectRaw('daerah, count(*) as jumlah')
    ->groupBy('daerah')
    ->orderByDesc('jumlah')
    ->limit(10)
    ->get()
    ->each(fn ($r) => printf("  %-24s %d\n", $r->daerah, $r->jumlah));

echo "\nPanjang teks daerah terpanjang:\n";

$panjang = DB::table($table)
    ->whereNotNull('daerah')
    ->where('daerah', '!=', '')
    ->selectRaw('max(length(daerah)) as p')
    ->first();

printf("  %d karakter\n", $panjang->p ?? 0);
