<?php

namespace Database\Seeders;

use App\Enums\KategoriResep;
use App\Enums\StatusResep;
use App\Enums\TingkatKesulitan;
use App\Models\Resep;
use App\Models\Simpanan;
use App\Models\Suka;
use App\Models\Ulasan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Data dummy khusus akun "Sari Pengguna" (sari-pengguna) supaya halaman
 * profil publiknya terlihat seperti akun yang benar-benar aktif:
 *
 *  - bio, lokasi, website, dan tanda centang verifikasi terisi
 *  - 8 resep berstatus terbit/terverifikasi (foto dari public/images/resep)
 *  - 2 resep unggulan (pinned) untuk menguji bagian "Resep Unggulan"
 *  - ulasan dari pengguna lain → rata_rating & jumlah_ulasan terisi
 *    (lewat Ulasan::create supaya UlasanObserver ikut menyinkronkan)
 *  - simpanan dari pengguna lain → jumlah_simpanan terisi
 *  - suka profil dari 25 pengguna + like tersebar di tiap resep
 *    (pengganti relasi follow yang sudah dihapus)
 *
 * Seeder ini idempoten: resep dicari dulu lewat slug sebelum dibuat,
 * aman dijalankan berkali-kali.
 */
class ProfilSariSeeder extends Seeder
{
    /** Username akun target. */
    private const USERNAME = 'sari-pengguna';

    /**
     * Resep-resep milik Sari. Nama berkas gambar = slug asli di
     * public/images/resep (380 berkas tersedia).
     *
     * @var array<int, array<string, mixed>>
     */
    private const RESEP = [
        [
            'judul' => 'Ayam Bakar Bumbu Kecap Manis',
            'slug' => 'ayam-bakar',
            'kategori' => KategoriResep::AyamUnggas,
            'daerah' => 'Jawa Tengah',
            'kesulitan' => TingkatKesulitan::Sedang,
            'waktu' => 75,
            'porsi' => 4,
            'deskripsi' => 'Ayam kampung dibakar sambil diolesi bumbu kecap manis dan bawang, menghasilkan lapisan karamel gosong yang harum. Disajikan dengan sambal terasi dan lalapan segar.',
            'bahan' => [
                ['nama' => 'Ayam kampung potong', 'jumlah' => 1, 'satuan' => 'ekor'],
                ['nama' => 'Kecap manis', 'jumlah' => 6, 'satuan' => 'sdm'],
                ['nama' => 'Bawang merah', 'jumlah' => 8, 'satuan' => 'siung'],
                ['nama' => 'Bawang putih', 'jumlah' => 4, 'satuan' => 'siung'],
                ['nama' => 'Kemiri', 'jumlah' => 3, 'satuan' => 'butir'],
                ['nama' => 'Kunyit', 'jumlah' => 2, 'satuan' => 'ruas'],
                ['nama' => 'Garam', 'jumlah' => 1, 'satuan' => 'sdt'],
            ],
            'langkah' => [
                'Ungkep ayam bersama bumbu halus sampai empuk dan bumbu meresap.',
                'Campur kecap manis dengan sedikit air, sisihkan untuk olesan.',
                'Bakar ayam di atas bara atau teflon sambil sesekali diolesi bumbu kecap.',
                'Balik ayan agar matang merata, bakar sampai permukaan mengilap kecokelatan.',
                'Sajikan hangat dengan sambal terasi dan lalapan.',
            ],
            'unggulan' => true,
            'urutan_unggulan' => 1,
        ],
        [
            'judul' => 'Bakmi Jawa Goreng Nyemek',
            'slug' => 'bakmi-jawa',
            'kategori' => KategoriResep::NasiKarbo,
            'daerah' => 'Yogyakarta',
            'kesulitan' => TingkatKesulitan::Sedang,
            'waktu' => 35,
            'porsi' => 2,
            'deskripsi' => 'Bakmi jawa dengan kuah nyemek — setengah basah, setengah kering — dimasak di wajan besi dengan telur, kol, dan aroma bakaran arang.',
            'bahan' => [
                ['nama' => 'Mie telur', 'jumlah' => 300, 'satuan' => 'gram'],
                ['nama' => 'Telur ayam', 'jumlah' => 2, 'satuan' => 'butir'],
                ['nama' => 'Kol iris', 'jumlah' => 100, 'satuan' => 'gram'],
                ['nama' => 'Daun bawang', 'jumlah' => 2, 'satuan' => 'batang'],
                ['nama' => 'Bawang putih', 'jumlah' => 3, 'satuan' => 'siung'],
                ['nama' => 'Kecap manis', 'jumlah' => 2, 'satuan' => 'sdm'],
            ],
            'langkah' => [
                'Tumis bawang putih sampai harum, masukkan telur dan orak-arik.',
                'Masukkan kol dan daun bawang, aduk sebentar sampai layu.',
                'Tambahkan mie dan kecap manis, aduk rata dengan sedikit air sampai nyemek.',
                'Masak sampai kuah menyusut setengah, sajikan panas dengan sambal.',
            ],
            'unggulan' => false,
        ],
        [
            'judul' => 'Bebek Goreng Lalapan Sambal Terasi',
            'slug' => 'bebek-goreng',
            'kategori' => KategoriResep::AyamUnggas,
            'daerah' => 'Jawa Barat',
            'kesulitan' => TingkatKesulitan::Sulit,
            'waktu' => 90,
            'porsi' => 4,
            'deskripsi' => 'Bebek diungkep dengan rempah sampai empuk lalu digoreng garing, disandingkan dengan lalapan dan sambal terasi yang menyengat.',
            'bahan' => [
                ['nama' => 'Bebek potong', 'jumlah' => 1, 'satuan' => 'ekor'],
                ['nama' => 'Asam jawa', 'jumlah' => 1, 'satuan' => 'sdm'],
                ['nama' => 'Daun salam', 'jumlah' => 3, 'satuan' => 'lembar'],
                ['nama' => 'Lengkuas', 'jumlah' => 3, 'satuan' => 'ruas'],
                ['nama' => 'Ketumbar', 'jumlah' => 1, 'satuan' => 'sdt'],
                ['nama' => 'Terasi bakar', 'jumlah' => 1, 'satuan' => 'sdt'],
            ],
            'langkah' => [
                'Ungkep bebek bersama rempah halus, daun salam, dan lengkuas sampai empuk.',
                'Angkat dan tiriskan, sisakan sedikit bumbu ungkepan.',
                'Goreng bebek dengan api sedang sampai kulitnya garing kecokelatan.',
                'Ulek semua bahan sambal terasi, sajikan bersama bebek dan lalapan.',
            ],
            'unggulan' => false,
        ],
        [
            'judul' => 'Ikan Patin Asam Pedas',
            'slug' => 'asam-pedas',
            'kategori' => KategoriResep::IkanSeafood,
            'daerah' => 'Sumatera Selatan',
            'kesulitan' => TingkatKesulitan::Sedang,
            'waktu' => 45,
            'porsi' => 4,
            'deskripsi' => 'Kuah asam pedas bening dengan potongan ikan patin segar, asam jawa, dan belimbing sayur yang menyegarkan.',
            'bahan' => [
                ['nama' => 'Ikan patin segar', 'jumlah' => 600, 'satuan' => 'gram'],
                ['nama' => 'Asam jawa', 'jumlah' => 2, 'satuan' => 'sdm'],
                ['nama' => 'Belimbing sayur', 'jumlah' => 3, 'satuan' => 'buah'],
                ['nama' => 'Cabai rawit', 'jumlah' => 10, 'satuan' => 'buah'],
                ['nama' => 'Tomat', 'jumlah' => 2, 'satuan' => 'buah'],
                ['nama' => 'Jahe', 'jumlah' => 2, 'satuan' => 'ruas'],
            ],
            'langkah' => [
                'Rebus air bersama jahe, asam jawa, dan cabai sampai mendidih.',
                'Masukkan potongan ikan patin, masak dengan api sedang.',
                'Tambahkan tomat dan belimbing sayur, masak sampai ikan matang.',
                'Koreksi rasa, sajikan hangat bersama nasi putih.',
            ],
            'unggulan' => false,
        ],
        [
            'judul' => 'Ayam Rica-Rica Manado',
            'slug' => 'ayam-rica-rica',
            'kategori' => KategoriResep::AyamUnggas,
            'daerah' => 'Sulawesi Utara',
            'kesulitan' => TingkatKesulitan::Sedang,
            'waktu' => 60,
            'porsi' => 4,
            'deskripsi' => 'Ayam dimasak dengan bumbu rica-rica pedas menyengat: cabai rawit, kemangi, dan jeruk purut yang tajam aromanya.',
            'bahan' => [
                ['nama' => 'Ayam potong', 'jumlah' => 800, 'satuan' => 'gram'],
                ['nama' => 'Cabai rawit', 'jumlah' => 20, 'satuan' => 'buah'],
                ['nama' => 'Cabai merah keriting', 'jumlah' => 10, 'satuan' => 'buah'],
                ['nama' => 'Daun kemangi', 'jumlah' => 2, 'satuan' => 'ikat'],
                ['nama' => 'Jeruk purut', 'jumlah' => 1, 'satuan' => 'buah'],
                ['nama' => 'Serai', 'jumlah' => 2, 'satuan' => 'batang'],
            ],
            'langkah' => [
                'Tumis bumbu halus bersama serai sampai harum dan minyak keluar.',
                'Masukkan potongan ayam, aduk sampai berubah warna.',
                'Tambahkan air sedikit, masak sampai ayam empuk dan bumbu mengental.',
                'Masukkan daun kemangi dan perasan jeruk purut, aduk sebentar, angkat.',
            ],
            'unggulan' => false,
        ],
        [
            'judul' => 'Bakso Solo Kuah Kaldu Sapi',
            'slug' => 'bakso-solo',
            'kategori' => KategoriResep::DagingSapi,
            'daerah' => 'Jawa Tengah',
            'kesulitan' => TingkatKesulitan::Sulit,
            'waktu' => 120,
            'porsi' => 6,
            'deskripsi' => 'Bakso kenyal khas Solo dengan kuah kaldu sapi bening yang gurih jernih, hasil merebus tulang selama berjam-jam.',
            'bahan' => [
                ['nama' => 'Daging sapi giling', 'jumlah' => 500, 'satuan' => 'gram'],
                ['nama' => 'Tepung sagu', 'jumlah' => 150, 'satuan' => 'gram'],
                ['nama' => 'Putih telur', 'jumlah' => 2, 'satuan' => 'butir'],
                ['nama' => 'Tulang sapi', 'jumlah' => 1, 'satuan' => 'kg'],
                ['nama' => 'Bawang putih', 'jumlah' => 6, 'satuan' => 'siung'],
                ['nama' => 'Daun bawang', 'jumlah' => 3, 'satuan' => 'batang'],
            ],
            'langkah' => [
                'Rebus tulang sapi dengan api kecil sampai kuah bening dan gurih.',
                'Campur daging giling, sagu, putih telur, dan bumbu, aduk sampai adonan kenyal.',
                'Bulatkan adonan, rebus sampai mengapung, angkat dan tiriskan.',
                'Sajikan bakso dalam kuah kaldu panas dengan taburan daun bawang dan bawang goreng.',
            ],
            'unggulan' => true,
            'urutan_unggulan' => 2,
        ],
        [
            'judul' => 'Arem-Arem Isi Daging Suwir',
            'slug' => 'arem-arem',
            'kategori' => KategoriResep::Jajanan,
            'daerah' => 'Jawa Barat',
            'kesulitan' => TingkatKesulitan::Sedang,
            'waktu' => 90,
            'porsi' => 6,
            'deskripsi' => 'Nasi ketan berisi daging suwir bumbu kuning, dibungkus daun pisang lalu dikukus sampai aromanya meresap.',
            'bahan' => [
                ['nama' => 'Beras ketan', 'jumlah' => 500, 'satuan' => 'gram'],
                ['nama' => 'Daging sapi', 'jumlah' => 300, 'satuan' => 'gram'],
                ['nama' => 'Santan', 'jumlah' => 400, 'satuan' => 'ml'],
                ['nama' => 'Daun pisang', 'jumlah' => 10, 'satuan' => 'lembar'],
                ['nama' => 'Kunyit', 'jumlah' => 2, 'satuan' => 'ruas'],
                ['nama' => 'Kemiri', 'jumlah' => 3, 'satuan' => 'butir'],
            ],
            'langkah' => [
                'Masak ketan bersama santan dan sedikit garam sampai matang pulen.',
                'Rebus daging sampai empuk, suwir halus, tumis bersama bumbu kuning.',
                'Ambil selembar daun pisang, isi ketan dan daging suwir, gulung rapat.',
                'Kukus arem-arem selama 30 menit sampai bumbu meresap, sajikan hangat.',
            ],
            'unggulan' => false,
        ],
        [
            'judul' => 'Ayam Betutu Bali',
            'slug' => 'betutu',
            'kategori' => KategoriResep::AyamUnggas,
            'daerah' => 'Bali',
            'kesulitan' => TingkatKesulitan::Sulit,
            'waktu' => 180,
            'porsi' => 4,
            'deskripsi' => 'Ayam utuh dimasak lama dalam bumbu basa genep Bali, dibungkus daun pisang dan dipanggang sampai dagingnya luruh dari tulang.',
            'bahan' => [
                ['nama' => 'Ayam kampung utuh', 'jumlah' => 1, 'satuan' => 'ekor'],
                ['nama' => 'Basa genep', 'jumlah' => 150, 'satuan' => 'gram'],
                ['nama' => 'Kencur', 'jumlah' => 3, 'satuan' => 'ruas'],
                ['nama' => 'Sereh', 'jumlah' => 3, 'satuan' => 'batang'],
                ['nama' => 'Daun pisang', 'jumlah' => 6, 'satuan' => 'lembar'],
                ['nama' => 'Minyak kelapa', 'jumlah' => 3, 'satuan' => 'sdm'],
            ],
            'langkah' => [
                'Lumuri ayam dengan basa genep dan minyak kelapa, diamkan satu jam.',
                'Bungkus rapat dengan daun pisang, sematkan lidi di setiap ujung.',
                'Panggang di atas bara kecil sambil dibalik, atau oven 160°C selama dua jam.',
                'Buka bungkusan, sajikan dengan sambal matah dan nasi hangat.',
            ],
            'unggulan' => false,
        ],
    ];

    public function run(): void
    {
        $sari = User::query()->where('username', self::USERNAME)->first();

        if (! $sari) {
            $this->command?->error('Pengguna "'.self::USERNAME.'" tidak ditemukan — jalankan UserSeeder lebih dulu.');

            return;
        }

        $this->isiProfil($sari);

        $resep = $this->buatResep($sari);

        $this->ulasan($resep);
        $this->simpanan($resep);
        $this->sukaProfil($sari);
        $this->sukaResep($resep);

        $this->command?->info(
            'Profil '.self::USERNAME.' siap: '.$resep->count().' resep, '.
            Ulasan::query()->whereIn('resep_id', $resep->pluck('id'))->count().' ulasan, '.
            Simpanan::query()->whereIn('resep_id', $resep->pluck('id'))->count().' simpanan, '.
            Suka::query()->where('sukaable_type', Suka::PROFIL)->where('sukaable_id', $sari->id)->count().' suka profil, '.
            Suka::query()->where('sukaable_type', Suka::RESEP)->whereIn('sukaable_id', $resep->pluck('id'))->count().' like resep.',
        );
    }

    /**
     * Lengkapi kolom profil yang tampil di halaman publik.
     */
    private function isiProfil(User $sari): void
    {
        $sari->update([
            'bio' => 'Ibu rumah tangga dari Bandung yang hobi memasak masakan Sunda. Suka bereksperimen dengan rempah tradisional dan mencatat resep keluarga supaya tidak hilang.',
            'lokasi' => 'Bandung, Jawa Barat',
            'website' => 'https://instagram.com/sari.dapur.sunda',
            'verified' => true,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Resep>
     */
    private function buatResep(User $sari)
    {
        $koleksi = collect();

        foreach (self::RESEP as $r) {
            $resep = Resep::query()->updateOrCreate(
                ['slug' => $r['slug']],
                [
                    'user_id' => $sari->id,
                    'judul' => $r['judul'],
                    'deskripsi' => $r['deskripsi'],
                    'gambar' => 'images/resep/'.$r['slug'].'.jpg',
                    'daerah' => $r['daerah'],
                    'kategori' => $r['kategori'],
                    'tingkat_kesulitan' => $r['kesulitan'],
                    // Campuran terbit & terverifikasi — keduanya lolos scopeTerbit().
                    'status' => StatusResep::Terverifikasi,
                    'waktu_masak_menit' => $r['waktu'],
                    'porsi' => $r['porsi'],
                    'energi_kkal' => 250 + $r['waktu'] * 2,
                    'protein_g' => 22,
                    'lemak_g' => 12,
                    'karbohidrat_g' => 18,
                    'gizi_mode' => 'otomatis',
                    'gizi_cakupan' => 100,
                    'hasil_total' => $r['porsi'] * 250,
                    'satuan_hasil' => 'gram',
                    'alat' => ['Wajan', 'Panci', 'Kompor'],
                    'bahan' => $r['bahan'],
                    'langkah' => $r['langkah'],
                    'unggulan' => $r['unggulan'],
                    'urutan_unggulan' => $r['urutan_unggulan'] ?? 0,
                    // Sebar created_at supaya tab "Terbaru" terlihat berisi,
                    // bukan semuanya berasal dari satu detik yang sama.
                    'created_at' => now()->subDays(30 + array_search($r, self::RESEP) * 9),
                    'updated_at' => now(),
                ],
            );

            $koleksi->push($resep);
        }

        return $koleksi;
    }

    /**
     * Ulasan dari pengguna lain.
     *
     * Dibuat lewat Ulasan::create — BUKAN query massal — supaya
     * UlasanObserver ikut menyalakan sinkronisasi rata_rating dan
     * jumlah_ulasan di tabel resep.
     *
     * @param  \Illuminate\Support\Collection<int, Resep>  $resep
     */
    private function ulasan($resep): void
    {
        $ulas = [
            [4, 'Bumbunya meresap banget, keluarga saya suka. Besok saya ulang lagi.'],
            [5, 'Resep paling berhasil yang pernah saya coba. Anti gagal!'],
            [5, 'Langkahnya jelas, hasilnya persis seperti foto.'],
            [4, 'Enak, tapi saya kurangi pedasnya sedikit untuk anak-anak.'],
            [5, 'Waktu masaknya agak lama tapi hasilnya sepadan.'],
            [3, 'Oke, tapi menurut saya kurang garam. Saya tambah sendiri.'],
            [4, 'Jadi langganan di rumah. Terima kasih sudah berbagi.'],
            [5, 'Sederhana tapi nendang. recommended!'],
        ];

        $penilai = User::query()
            ->where('username', '!=', self::USERNAME)
            ->inRandomOrder()
            ->limit(count($ulas))
            ->get();

        foreach ($ulas as $i => [$rating, $komentar]) {
            $pengguna = $penilai[$i] ?? null;

            if (! $pengguna) {
                continue;
            }

            Ulasan::query()->firstOrCreate(
                ['resep_id' => $resep[$i]->id, 'user_id' => $pengguna->id],
                ['rating' => $rating, 'komentar' => $komentar],
            );
        }
    }

    /**
     * Simpanan dari pengguna lain — terlihat sebagai jumlah_simpanan
     * di grid profil.
     *
     * Jumlahnya sengaja DIBUAT BERVARIASI JAUH (3 sampai 31), bukan seragam:
     * dengan angka seragam tab "Paling Disimpan" menghasilkan urutan yang
     * sama persis dengan "Terbaru" dan tab terbaca sebagai tidak berfungsi.
     *
     * @param  \Illuminate\Support\Collection<int, Resep>  $resep
     */
    private function simpanan($resep): void
    {
        // Buang simpanan lama pada resep Sari dulu: tanpa ini angka lama
        // bercampur dengan jumlah baru dan tab "Paling Disimpan" kembali
        // seragam. Simpanan TIDAK punya observer (jumlah dihitung withCount
        // on the fly), jadi penghapusan massal di sini aman.
        Simpanan::query()
            ->whereIn('resep_id', $resep->pluck('id'))
            ->delete();

        $penyimang = User::query()
            ->where('username', '!=', self::USERNAME)
            ->inRandomOrder()
            ->limit(80)
            ->get();

        // Urutan acak tapi tetap teracak ulang tiap run supaya tab
        // "Paling Disimpan" selalu menampilkan urutan berbeda dari
        // "Terbaru" (created_at) dan "Paling Disukai" (rata_rating).
        $jumlahPerResep = [31, 4, 18, 9, 3, 22, 6, 14];

        foreach ($resep as $i => $r) {
            $jumlah = $jumlahPerResep[$i] ?? 5;

            foreach ($penyimang->slice(0, $jumlah) as $pengguna) {
                Simpanan::query()->firstOrCreate(
                    ['user_id' => $pengguna->id, 'resep_id' => $r->id],
                );
            }

            // Geser daftar penyimang supaya antar resep tidak berbagi
            // orang yang sama persis di urutan yang sama.
            $penyimang = $penyimang->slice($jumlah)->values()->merge($penyimang->take($jumlah))->values();
        }
    }

    /**
     * Suka profil dari pengguna lain — pengganti relasi follow yang sudah
     * dihapus. 25 orang menyukai profil Sari (angka "suka profil" di stats).
     */
    private function sukaProfil(User $sari): void
    {
        User::query()
            ->where('username', '!=', self::USERNAME)
            ->inRandomOrder()
            ->limit(25)
            ->pluck('id')
            ->each(fn ($id) => Suka::query()->firstOrCreate([
                'user_id' => $id,
                'sukaable_type' => Suka::PROFIL,
                'sukaable_id' => $sari->id,
            ]));
    }

    /**
     * Like (suka resep) tersebar dengan jumlah bervariasi supaya skor
     * unggulan otomatis punya rentang angka yang realistis — bukan semua
     * resep dengan like sama rata.
     *
     * Like lama di resep Sari dihapus dulu supaya seeder tetap idempoten
     * (bisa dijalankan ulang tanpa menggandakan angka).
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\Resep>  $resep
     */
    private function sukaResep($resep): void
    {
        Suka::query()
            ->where('sukaable_type', Suka::RESEP)
            ->whereIn('sukaable_id', $resep->pluck('id'))
            ->delete();

        // Jumlah like per resep, searah urutan koleksi (terbaru dulu).
        // Bervariasi dari 19 turun ke 5: resep terbaru paling banyak disukai
        // supaya bagian "Paling Disukai" di tab punya urutan jelas.
        $jumlahPerResep = [19, 12, 8, 15, 5, 11, 7, 14];

        foreach ($resep->values() as $i => $masakan) {
            $jumlah = $jumlahPerResep[$i] ?? 5;

            User::query()
                ->where('username', '!=', self::USERNAME)
                ->inRandomOrder()
                ->limit($jumlah)
                ->pluck('id')
                ->each(fn ($id) => Suka::query()->firstOrCreate([
                    'user_id' => $id,
                    'sukaable_type' => Suka::RESEP,
                    'sukaable_id' => $masakan->id,
                ]));
        }
    }
}
