<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * Penjaga: setiap nama rute yang dipanggil dari JavaScript harus benar-benar
 * terdaftar di Laravel.
 *
 * Kenapa test, bukan audit manual: kegagalan jenis ini tidak melempar error.
 * Ia hanya menghasilkan tautan yang mengarah ke kehampaan, dan baru ketahuan
 * ketika seseorang mengkliknya. Masalah nyata yang pernah terjadi: `/resep`
 * hilang dari navbar setelah login, sehingga pengguna yang baru masuk tidak
 * punya cara mencari resep sama sekali kecuali mengetik URL-nya sendiri —
 * padahal halaman itu memuat ratusan resep dan kotak pencariannya.
 */
class RouteNamesInJsTest extends TestCase
{
    public function test_setiap_nama_rute_di_javascript_terdaftar(): void
    {
        $dipakai = $this->namaRuteDariJavascript();

        $this->assertNotEmpty(
            $dipakai,
            'Tidak menemukan satu pun pemanggilan route() di resources/js. Pola pindainya mungkin sudah tidak cocok, sehingga test ini diam-diam tidak lagi menguji apa pun.',
        );

        $hilang = collect($dipakai)
            ->reject(fn (string $nama) => Route::has($nama))
            ->values();

        $this->assertSame(
            [],
            $hilang->all(),
            'Nama rute berikut dipakai di JavaScript tetapi tidak terdaftar di Laravel: '.$hilang->implode(', '),
        );
    }

    /**
     * `route(tautanStudio)` di navbar dan di Dashboard dihitung dari
     * permissions, jadi pindai regex di atas melewatkannya. Keduanya diperiksa
     * terpisah di sini — bila salah satu hilang, navbar tetap akan memuat
     * sebuah tautan yang mengarah ke rute bernama palsu.
     */
    public function test_tautan_studio_yang_dihitung_dari_permissions_masih_ada(): void
    {
        $this->assertTrue(Route::has('studio.index'), 'studio.index hilang — navbar Pengguna akan rusak.');
    }

    /**
     * Kumpulkan seluruh nama rute literal dari berkas JS/JSX.
     *
     * Pemanggilan dinamis seperti `route(tautanStudio)` sengaja tidak
     * dicocokkan — regex hanya menangkap bentik string. Itulah gunanya test
     * kedua di atas.
     *
     * @return array<int, string>
     */
    private function namaRuteDariJavascript(): array
    {
        $pola = '/route\(\s*[\'"]([a-zA-Z0-9._-]+)[\'"]/';
        $tertemu = [];

        $berkas = Finder::create()
            ->files()
            ->in(resource_path('js'))
            ->name(['*.js', '*.jsx']);

        foreach ($berkas as $isi) {
            $isi = file_get_contents($isi->getPathname());

            if ($isi === false) {
                continue;
            }

            preg_match_all($pola, $isi, $cocok);

            foreach ($cocok[1] as $nama) {
                $tertemu[$nama] = true;
            }
        }

        return array_keys($tertemu);
    }
}
