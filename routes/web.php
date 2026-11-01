<?php

use App\Enums\KategoriResep;
use App\Http\Controllers\CariController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\CoverController;
use App\Http\Controllers\EditProfilController;
use App\Http\Controllers\SukaController;
use App\Http\Controllers\KreditFotoController;
use App\Http\Controllers\KurasiController;
use App\Http\Controllers\ProfilPenggunaController;
use App\Http\Controllers\ResepController;
use App\Http\Controllers\SimpananController;
use App\Http\Controllers\StudioController;
use App\Http\Controllers\UlasanController;
use App\Http\Controllers\UserManagementController;
use App\Models\Resep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (Request $request) {
    // Chip kategori & kolom pencarian sama-sama menyaring lewat query string:
    // /?kategori=jajanan atau /?q=rendang (FTS PostgreSQL).
    $kategoriDipilih = KategoriResep::tryFrom((string) $request->query('kategori'));
    $kataCari = trim((string) $request->query('q'));

    // Satu saringan untuk dipakai ulang oleh tiga query berbeda (jumlah
    // hasil, kolam slideshow, grid). Isinya sama persis dengan yang dipakai
    // halaman /resep — lihat Resep::scopeDirektori(), supaya kedua halaman
    // tidak mungkin berbeda pandangan tentang resep mana yang layak tayang.
    $saring = fn () => Resep::query()->direktori($kategoriDipilih, $kataCari);

    // Resep berfoto diurutkan mendahului yang belum ada fotonya, supaya grid
    // landing selalu penuh gambar. Resep tanpa foto tetap ikut serta
    // (dengan placeholder) — inilah yang membuat chip kategori tidak pernah
    // kosong walau sebagian besar resep belum diunggah fotonya.
    // Aturannya ada di Resep::scopeEtalase(), dipakai bersama oleh direktori
    // /resep dan blok resep terkait di halaman detail.

    $jumlahHasil = $saring()->count();

    // Etalase hero kini berupa kolam slideshow: kirim sampai 5 resep berfoto
    // (urutannya diacak) supaya browser memutarnya sendiri — pergantian slide
    // murni di sisi klien, tanpa request tambahan.
    //
    // Prioritasnya: resep berfoto yang sesuai saringan -> seluruh resep berfoto
    // -> hasil saringan apa adanya. Cabang kedua dipakai karena slideshow dengan
    // satu kartu tidak ada gunanya, dan cabang terakhir membuat hero tidak
    // pernah jadi kotak kosong walau belum ada satu pun resep berfoto.
    $kolamUnggulan = collect();

    if ($jumlahHasil > 0) {
        $kolamUnggulan = $saring()
            ->whereNotNull('gambar')
            ->etalase()
            ->with('penulis:id,name,username,avatar_path')
            ->withCount('suka as jumlah_suka')
            ->withCount('disimpanOleh as jumlah_simpanan')
            ->take(5)
            ->get();

        if ($kolamUnggulan->count() < 2) {
            $kolamUnggulan = Resep::query()
                ->terbit()
                ->with('penulis:id,name,username,avatar_path')
                ->withCount('suka as jumlah_suka')
                ->withCount('disimpanOleh as jumlah_simpanan')
                ->whereNotNull('gambar')
                ->inRandomOrder()
                ->take(5)
                ->get();
        }

        if ($kolamUnggulan->isEmpty()) {
            $kolamUnggulan = $saring()
                ->etalase()
                ->with('penulis:id,name,username,avatar_path')
                ->withCount('suka as jumlah_suka')
                ->withCount('disimpanOleh as jumlah_simpanan')
                ->take(5)
                ->get();
        }
    }

    return Inertia::render('Welcome', [
        // canLogin / canRegister tidak dikirim lagi di sini — keduanya sudah
        // dibagikan lewat HandleInertiaRequests::share(), sehingga halaman
        // lain seperti /resep dan detail resep ikut mendapatkannya.
        'kategoriAktif' => $kategoriDipilih?->value,
        'kategoriTersedia' => KategoriResep::untukProps(),
        'kataCari' => $kataCari,
        // Angka jujur dari database — tidak ada lagi klaim "12.000+" di JSX.
        'jumlahResep' => Resep::query()->terbit()->count(),
        'jumlahHasil' => $jumlahHasil,
        'unggulan' => $kolamUnggulan,
        // Grid menampilkan sampai 4 kartu (satu baris, mengikuti desain Stitch).
        // Kolam hero sengaja tidak dikeluarkan dari grid: dulu, ketika foto baru
        // ada lima, mengeluarkannya justru membuat grid kehilangan seluruh
        // fotonya. Batas empat di sini juga sudah dijalankan oleh SQL, bukan oleh
        // Collection, sehingga request tidak pernah memuat seluruh tabel.
        'resep' => $saring()->etalase()
            ->with('penulis:id,name,username,avatar_path')
            ->withCount('suka as jumlah_suka')
            ->withCount('disimpanOleh as jumlah_simpanan')
            ->take(4)
            ->get(),
    ]);
})->name('home');

// Kredit foto — wajib ada karena hampir seluruh foto berlisensi CC BY-SA.
// Nama rutenya 'kredit-foto', bukan 'kredit.foto' — Ziggy menyusun daftar dari
// nama rute, dan footer memanggil route('kredit-foto'). Dua titik dan dua
// tanda hubung adalah dua kunci berbeda; salah satu pasti error di sisi klien.
Route::get('/kredit-foto', [KreditFotoController::class, 'index'])->name('kredit-foto');

// Direktori resep dan halaman detailnya. Sengaja tanpa middleware auth:
// seluruh isinya sudah terlihat di beranda, dan pagarnya adalah status resep
// (scope `terbit()`), bukan akun pengunjung. `resep` harus didahulukan
// sebelum `resep/{slug}` — Laravel mencocokkan rute secara berurutan.
Route::get('/resep', [ResepController::class, 'index'])->name('resep.index');
Route::get('/resep/{slug}', [ResepController::class, 'show'])->name('resep.show');

Route::get('/dashboard', function (Request $request) {
    // Dashboard sekarang menampilkan milik Anda — bukan katalog.
    $pengguna = $request->user();

    // Kueri dasar simpanan yang layak tampil. Dipakai berkali-kali di bawah:
    // angka kartu, isi grid, dan ringkasan harus berangkat dari kumpulan yang
    // sama, kalau tidak "3 tersimpan" bisa muncul di atas grid berisi 2 kartu.
    $simpananLayak = fn () => $pengguna->simpanan()
        ->whereHas('resep', fn ($q) => $q->terbit());

    $tersimpan = $simpananLayak()
        // 8 resep yang SIMPAN — bukan 8 resep terbit terbaru.
        // Ini satu-satunya data yang tidak bisa ada di /resep: karena
        // /resep tidak tahu apa yang Anda simpan. Isi dashboard jadi
        // pasti berbeda dari katalog.
        //
        // Relasi simpanan()->resep — scope terbit() wajib: jika sebuah
        // resep turun ke Draft setelah disimpan, kartinya tetap harus
        // klik-able, jadi kami filter di level relasi.
        ->with(['resep.penulis:id,name,username,avatar_path'])
        ->latest('simpanan.created_at')
        ->limit(8)
        ->get()
        ->map(fn ($simpanan) => $simpanan->resep)
        ->filter()
        ->values();

    // Jumlah like per resep tidak bisa memakai withCount() di sini karena
    // koleksinya lahir dari relasi simpanan (bukan query resep). loadCount
    // menghitungnya dalam satu query terpisah untuk seluruh koleksi — sama
    // efisennya dengan withCount dan tidak mungkin ketinggalan per kartu.
    // map+filter+values menghasilkan kumpulan model Resep; dibungkus ulang
    // menjadi Eloquent Collection secara eksplisit karena pluck() lama
    // mengembalikan Collection biasa yang tidak punya loadCount — sumber
    // error 500 "Method Collection::loadCount does not exist" di dashboard.
    $tersimpan = new \Illuminate\Database\Eloquent\Collection($tersimpan->all());
    $tersimpan->loadCount('suka as jumlah_suka');
    $tersimpan->loadCount('disimpanOleh as jumlah_simpanan');

    // Ringkasan dihitung dari SELURUH simpanan, bukan 8 kartu pertama:
    // pengguna dengan 20 resep tetap harus melihat kategori favorit yang
    // benar, bukan yang kebetulan menang di delapan kartu teratas.
    $ringkasanMentah = $simpananLayak()
        ->join('resep', 'resep.id', '=', 'simpanan.resep_id')
        ->selectRaw('resep.kategori, COUNT(*) as jumlah')
        ->groupBy('resep.kategori')
        ->orderByDesc('jumlah')
        ->get();

    $kategoriFavorit = $ringkasanMentah->first()?->kategori;

    // Kapan terakhir kali pengguna menandai sesuatu. Ini fakta simpanan, bukan
    // klaim tentang memasak: menyimpan resep tidak berarti ia memasaknya, dan
    // aplikasi ini memang tidak mencatat aktivitas memasak sama sekali.
    // Angka "total waktu masak" dulu ada di sini dan sengaja dibuang — ia
    // menjumlahkan perkiraan durasi resep yang belum tentu pernah dimasak.
    $terakhirDisimpan = $simpananLayak()->max('simpanan.created_at');

    // Rekomendasi: resep tayang yang BELUM disimpan, dari kategori favorit
    // lebih dulu. Tanpa bagian ini beranda hanya mengulang /simpanan, dan
    // tidak punya alasan untuk dikunjungi kembali.
    $sudahDisimpan = $tersimpan->pluck('id');

    $rekomendasi = Resep::query()
        ->terbit()
        ->etalase()
        ->with('penulis:id,name,username,avatar_path')
        ->withCount('suka as jumlah_suka')
        ->withCount('disimpanOleh as jumlah_simpanan')
        ->whereNotIn('id', $sudahDisimpan)
        ->when($kategoriFavorit, fn ($q, $kategori) => $q->where('kategori', $kategori))
        ->take(4)
        ->get();

    // Kalau kategori favorit hanya berisi resep yang sudah disimpan semua,
    // isi sisanya dari kategori lain — lebih berguna daripada blok kosong.
    if ($rekomendasi->count() < 4) {
        $rekomendasi = $rekomendasi->concat(
            Resep::query()
                ->terbit()
                ->etalase()
                ->with('penulis:id,name,username,avatar_path')
                ->withCount('suka as jumlah_suka')
                ->withCount('disimpanOleh as jumlah_simpanan')
                ->whereNotIn('id', $sudahDisimpan)
                ->whereNotIn('id', $rekomendasi->pluck('id'))
                ->when($kategoriFavorit, fn ($q, $kategori) => $q->where('kategori', '!=', $kategori))
                ->take(4 - $rekomendasi->count())
                ->get()
        );
    }

    return Inertia::render('Dashboard', [
        // Angka harus dihitung, bukan ditulis. "Resep tersimpan" pernah
        // memuat literal '0' yang tetap nol walaupun pengguna sudah
        // menyimpan lima resep.
        'jumlahTersimpan' => $simpananLayak()->count(),
        // Bukan "berapa disimpan", tapi "berapa resep yang DIA BUAT di
        // Studio". Dipakai panel "buka dapur": begitu pengguna sudah
        // membuat walau satu resep, dapurnya dianggap sudah terbuka dan
        // ajakan membuka dapur tidak lagi relevan.
        'jumlahResep' => $pengguna->reseps()->count(),
        'tersimpan' => $tersimpan,
        // Ringkasan untuk kartu statistik. `kategoriFavorit` dikirim sebagai
        // label Indonesia, bukan value enum, supaya React tidak perlu daftar
        // label kedua — sumbernya tetap enum PHP.
        'ringkasan' => [
            'kategoriFavorit' => $kategoriFavorit
                ? KategoriResep::tryFrom($kategoriFavorit)?->label()
                : null,
            // ISO-8601 supaya React bisa menghitung "2 hari lalu" di zona
            // waktu pengguna sendiri, bukan zona server.
            'terakhirDisimpan' => $terakhirDisimpan
                ? \Illuminate\Support\Carbon::parse($terakhirDisimpan)->toIso8601String()
                : null,
        ],
        'rekomendasi' => $rekomendasi->values(),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::name('studio.')->group(function () {
        Route::get('/studio-dapur', [StudioController::class, 'index'])->name('index');
        Route::get('/studio-dapur/buat', [StudioController::class, 'create'])->name('create');
        Route::post('/studio-dapur', [StudioController::class, 'store'])->name('store');
        // Pengaturan dapur harus diurutkan SEBELUM route parameter {resep},
        // supaya PUT /studio-dapur/pengaturan tidak ditangkap oleh
        // PUT /studio-dapur/{resep} dengan slug "pengaturan" (akibatnya 404
        // saat menyimpan pengaturan dapur).
        Route::get('/studio-dapur/{resep}/edit', [StudioController::class, 'edit'])->name('edit');
        Route::put('/studio-dapur/{resep}', [StudioController::class, 'update'])->name('update');
        Route::delete('/studio-dapur/{resep}', [StudioController::class, 'destroy'])->name('destroy');
    });

    // Ajukan / tarik pengajuan kurasi. Di dalam grup auth+verified di atas:
    // setiap pengguna yang login (Pengguna maupun Pengelola) boleh mengajukan
    // draft miliknya sendiri; policy `update` tetap menjaga kepemilikan per
    // baris.
    Route::group([], function () {
        Route::post('/studio-dapur/{resep}/ajukan', [StudioController::class, 'ajukanKurasi'])
            ->name('studio.ajukan-resep');
        Route::post('/studio-dapur/{resep}/tarik', [StudioController::class, 'tarikKurasi'])
            ->name('studio.tarik-resep');
    });

    // Kurasi resep — kewenangan mutlak Pengelola.
    Route::middleware('role:pengelola')->name('kurasi.')->group(function () {
        Route::get('/kurasi', [KurasiController::class, 'index'])->name('index');
        Route::post('/kurasi/{resep}/setujui', [KurasiController::class, 'setujui'])
            ->whereNumber('resep')->name('setujui');
        Route::post('/kurasi/{resep}/tolak', [KurasiController::class, 'tolak'])
            ->whereNumber('resep')->name('tolak');
        Route::post('/kurasi/{resep}/diperiksa', [KurasiController::class, 'tandaiDiperiksa'])
            ->whereNumber('resep')->name('diperiksa');
    });

    // Panel Pengelola — hanya Pengelola.
    Route::get('/panel/users', [UserManagementController::class, 'index'])
        ->middleware('role:pengelola')
        ->name('panel.users');

    Route::patch('/panel/users/{user}/role', [UserManagementController::class, 'updateRole'])
        ->middleware('role:pengelola')
        ->name('panel.users.role');
});

Route::middleware('auth')->group(function () {
    // Resep tersimpan — terbuka untuk semua peran, termasuk Penikmat.
    //
    // Sengaja di group `auth` biasa, bukan group `['auth', 'verified']`
    // di atas: menyimpan resep tidak merusak apa pun dan tidak terlihat
    // siapa pun lain, jadi memaksa verifikasi email di sini hanya menambah
    // gesekan tanpa melindungi siapa pun. Sebaliknya, Studio dan Panel
    // menyentuh data bersama sehingga layak menunggu verifikasi.
    Route::get('/simpanan', [SimpananController::class, 'index'])
        ->name('simpanan.index');

    // ID, bukan slug: halaman detail mengirim `resep.id`, dan angka tidak
    // berubah walau judul resep diedit — slug memang sengaja permanen, tetapi
    // ID lebih pendek dan tidak membingungkan bila diketik ulang.
    Route::post('/simpan/{resep}', [SimpananController::class, 'toggle'])
        ->whereNumber('resep')
        ->name('simpanan.toggle');

    // Ulasan & rating. Terbuka untuk semua peran yang sudah masuk, sama
    // seperti simpanan: menulis ulasan tidak merusak apa pun dan tidak
    // menyentuh data bersama, jadi tidak perlu menunggu verifikasi email.
    //
    // Menulis memakai ID resep (angka), sedangkan mengubah/menghapus memakai
    // ID ulasan. Keduanya dijaga policy, bukan middleware — batasnya bukan
    // peran melainkan kepemilikan baris ("apakah ini ulasan saya?").
    Route::post('/resep/{resep}/ulasan', [UlasanController::class, 'store'])
        ->whereNumber('resep')
        ->name('ulasan.store');

    Route::patch('/ulasan/{ulasan}', [UlasanController::class, 'update'])
        ->whereNumber('ulasan')
        ->name('ulasan.update');

    Route::delete('/ulasan/{ulasan}', [UlasanController::class, 'destroy'])
        ->whereNumber('ulasan')
        ->name('ulasan.destroy');

    Route::post('/ulasan/{ulasan}/balas', [UlasanController::class, 'balas'])
        ->whereNumber('ulasan')
        ->name('ulasan.balas');

    // Edit profil lengkap
    Route::get('/profil/edit', [EditProfilController::class, 'edit'])->name('profil.edit');
    Route::patch('/profil/edit', [EditProfilController::class, 'update'])->name('profil.update');
    Route::delete('/profil/edit', [EditProfilController::class, 'destroy'])->name('profil.destroy');

    // Foto profil. Rute `update` tanpa parameter user: yang bisa diganti
    // hanyalah foto milik sendiri, sehingga tidak ada ID di URL yang bisa
    // dicoba-coba untuk menimpa foto orang lain.
    Route::post('/profile/foto', [AvatarController::class, 'update'])->name('profile.avatar.update');
    Route::delete('/profile/foto', [AvatarController::class, 'destroy'])->name('profile.avatar.destroy');

    // Foto sampul profil (hanya milik sendiri).
    Route::post('/profile/cover', [CoverController::class, 'update'])->name('profile.cover.update');
    Route::delete('/profile/cover', [CoverController::class, 'destroy'])->name('profile.cover.destroy');
});

Route::get('/pengguna/{username}', [ProfilPenggunaController::class, 'show'])
    ->name('profil.show');

// Cari akun / profil kreator (publik, tanpa auth).
Route::get('/cari', [CariController::class, 'index'])
    ->name('cari.index');

// Like — pengganti follow. Toggle: ada baris → dihapus, tidak ada → dibuat.
// Keduanya butuh login; tombol di tampilan mengarahkan tamu ke /login.
Route::post('/pengguna/{username}/suka', [SukaController::class, 'profil'])
    ->middleware('auth')
    ->name('profil.suka');
Route::post('/resep/{slug}/suka', [SukaController::class, 'resep'])
    ->middleware('auth')
    ->name('resep.suka');

// Ikuti / henti ikuti pengguna — DIHAPUS: diganti sistem like
// (suka profil + suka resep) lewat SukaController.

require __DIR__.'/auth.php';
