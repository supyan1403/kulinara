import AvatarInisial from '@/Components/AvatarInisial';
import BintangRating from '@/Components/BintangRating';
import RecipeCard from '@/Components/RecipeCard';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { urlGambar } from '@/utils/gambar';
import { useState } from 'react';

/**
 * Halaman profil publik — mirip grid profil Instagram:
 *
 *  - Foto sampul di atas (cover)
 *  - Avatar, nama, @username, badge verifikasi, bio, lokasi, website
 *  - Tombol Edit Profil (profil sendiri) / Suka Profil (lain)
 *  - Statistik: resep terbit, suka profil, suka masakan
 *  - Tabs: Terbaru, Unggulan, Paling Disukai, Paling Disimpan
 *  - Grid resep mengikuti tab aktif — bagian "Unggulan" bukan blok terpisah
 *    lagi, melainkan SATU dari tab-tab ini (pengganti: user bingung melihat
 *    dua blok grid berturut-turut yang isinya kelihatan mirip)
 */
export default function ProfilPengguna({
    pengguna,
    resep = [],
    unggulan = [],
    rataRating,
    jumlahResep,
    jumlahUlasan,
    sukaProfilCount,
    sukaMasakanCount,
    sudahSukaProfil,
    mode = 'publik',
    resepTersimpan = [],
    resepDisukai = [],
    koleksi = [],
    statsKoleksi = null,
}) {
    const { auth } = usePage().props;
    const isOwn = auth?.user?.id === pengguna.id || auth?.user?.username === pengguna.username;
    const [tab, setTab] = useState(mode === 'koleksi' ? 'disimpan' : 'terbaru');

    // Halaman aktif untuk grid — direset tiap pindah tab supaya pembaca
    // tidak terjebak di halaman kosong saat tab barunya lebih pendek.
    const [halaman, setHalaman] = useState(1);

    const pindahTab = (kunci) => {
        setTab(kunci);
        setHalaman(1);
    };

    const sumberWebsite = pengguna.website
        ? pengguna.website.startsWith('http')
            ? pengguna.website
            : `https://${pengguna.website}`
        : null;

    const verifikasi = pengguna.verified ? (
        <span
            title="Akun terverifikasi"
            className="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-sage-dark text-white"
        >
            <svg className="h-3 w-3" fill="none" stroke="currentColor" strokeWidth="3" viewBox="0 0 24 24" aria-hidden="true">
                <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
        </span>
    ) : null;

    // ── Tab & isi grid ──
    // Label per tab dipakai BAIK di tombol maupun di judul grid, sehingga
    // judul selalu ikut berubah tiap pindah tab — perpindahan yang jelas
    // terbaca, bukan cuma urutan kartu yang bergeser diam-diam.
    //
    // Dua wajah tab:
    //  - `koleksi` (profil sendiri): koleksi pribadi — resep yang disimpan
    //    dan yang disukai. Murni daftar pribadi, bukan peringkat.
    //  - `publik` (profil orang lain): etalase resep buatan pemilik,
    //    diurutkan menurut tab masing-masing.
    const labelTab = {
        disimpan: 'Disimpan',
        disukai: 'Disukai',
        terbaru: 'Terbaru',
        unggulan: 'Unggulan',
    };

    const daftarTab =
        mode === 'koleksi'
            ? ['disimpan', 'disukai', 'unggulan'].filter(
                  (kunci) => kunci !== 'unggulan' || unggulan.length > 0,
              )
            : ['terbaru', 'unggulan', 'disukai', 'disimpan'].filter(
                  // Tab Unggulan hanya muncul kalau memang ada isinya — profil tanpa
                  // resep tidak menampilkan tab yang pasti kosong.
                  (kunci) => kunci !== 'unggulan' || unggulan.length > 0,
              );

    // ── Urutan resep per tab ──
    const urutkan = (data) => {
        const salinan = [...data];
        if (tab === 'disukai') {
            salinan.sort((a, b) => {
                const ra = Number(a.rata_rating) || 0;
                const rb = Number(b.rata_rating) || 0;
                if (rb !== ra) return rb - ra;
                return (b.jumlah_ulasan || 0) - (a.jumlah_ulasan || 0);
            });
        } else if (tab === 'disimpan') {
            salinan.sort((a, b) => {
                const sa = Number(a.jumlah_simpanan) || 0;
                const sb = Number(b.jumlah_simpanan) || 0;
                if (sb !== sa) return sb - sa;
                return (b.jumlah_ulasan || 0) - (a.jumlah_ulasan || 0);
            });
        }
        return salinan;
    };

    // Isi grid mengikuti tab: "Unggulan" = top skor dari server, sisanya
    // seluruh resep terbit yang diurutkan ulang di klien.
    // Mode koleksi: tab memilih sumber data (disimpan / disukai), bukan
    // sekadar mengurutkan satu daftar yang sama.
    const tampilan =
        mode === 'koleksi'
            ? tab === 'disukai'
                ? resepDisukai
                : tab === 'unggulan'
                  ? unggulan
                  : resepTersimpan
            : tab === 'unggulan'
              ? unggulan
              : urutkan(resep);

    // ── Paginasi klien ──
    // Data tab sudah dimuat penuh dari server (dan sebagian diurutkan di
    // klien), jadi pengirisannya dilakukan di sini — bukan lewat permintaan
    // baru. Cukup untuk membuat halaman tidak panjang ke bawah.
    const PER_HALAMAN = 12;
    const totalHalaman = Math.max(1, Math.ceil(tampilan.length / PER_HALAMAN));
    // Guard: bila jumlah data menyusut (mis. pindah ke tab lebih pendek),
    // halaman dibatasi ke halaman terakhir yang masih berisi.
    const halamanAman = Math.min(halaman, totalHalaman);
    const mulai = (halamanAman - 1) * PER_HALAMAN;
    const kartuTerlihat = tampilan.slice(mulai, mulai + PER_HALAMAN);

    /*
        Toggle suka profil — satu rute POST yang membalik sendiri keadaannya
        (ada baris → dihapus, tidak ada → dibuat), jadi tombol ini tidak
        memilih antara dua permintaan berbeda seperti tombol Ikuti lama.

        `sudahSukaProfil` datang dari server; `sukaSaya` dipakai sebagai
        lapisan kedua supaya keadaan hati ikut berubah instan walau halaman
        belum dimuat ulang.
    */
    const sukaProfil = () => {
        router.post(
            route('profil.suka', pengguna.username),
            {},
            { preserveScroll: true },
        );
    };

    return (
        <AuthenticatedLayout motif overlay>
            <Head title={`Profil ${pengguna.name}`} />

            {/* ── Cover ── */}
            {/*
                Navbar memakai mode `fixed` (overlay), sehingga baris pertama
                tampilan adalah bilah 64px (h-16). Padding ini menurunkan sampul
                MENTOK di bawahnya, tanpa sela — tanpa ini 64px gambar sampul
                tertutup bar cream yang menempel di puncak layar.
            */}
            <div className="pt-16">
                <div className="relative aspect-[16/9] max-h-[420px] w-full overflow-hidden rounded-b-3xl">
                    {pengguna.foto_sampul ? (
                        <img
                            src={urlGambar(pengguna.foto_sampul)}
                            alt="Foto sampul"
                            className="h-full w-full object-cover"
                        />
                    ) : (
                        <div className="h-full w-full bg-gradient-to-b from-muted/20 to-transparent" />
                    )}
                    {/* Overlay halus supaya teks di atas cover tetap readable */}
                    <div className="absolute inset-0 bg-black/20" />
                </div>
            </div>

            <div className="mx-auto max-w-shell px-4 pt-6 sm:px-4 lg:px-6 xl:px-8">
                {/* ── Kartu identitas ── */}
                <div className="relative z-10 -mt-10 rounded-2xl border border-border bg-surface p-4 shadow-card sm:-mt-16 sm:p-6 lg:p-8">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between sm:gap-6">
                        <div className="flex items-start gap-4 sm:gap-6">
                            <AvatarInisial
                                nama={pengguna.name}
                                foto={pengguna.avatar_path}
                                ukuran="xl"
                                posisi="50% 8%"
                                className="ring-4 ring-white shadow-card"
                            />

                            <div className="min-w-0 pt-1">
                                <h1 className="flex flex-wrap items-center gap-2 font-display text-2xl font-bold leading-tight tracking-tight text-charcoal sm:text-3xl">
                                    {pengguna.name}
                                    {verifikasi}
                                </h1>
                                {pengguna.username && (
                                    <p className="mt-1 font-mono text-sm text-muted">
                                        @{pengguna.username}
                                    </p>
                                )}

                                <div className="mt-4 flex flex-wrap items-center gap-2">
                                    {isOwn && (
                                        <Link
                                            href={route('profil.edit')}
                                            className="inline-flex items-center gap-2 rounded-lg border border-border bg-surface px-3 py-2 text-sm font-semibold text-charcoal transition hover:bg-linen"
                                        >
                                            <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                                            </svg>
                                            Edit Profil
                                        </Link>
                                    )}

                                    {!isOwn && (
                                        auth?.user ? (
                                            <button
                                                type="button"
                                                onClick={sukaProfil}
                                                aria-pressed={sudahSukaProfil}
                                                aria-label={
                                                    sudahSukaProfil
                                                        ? 'Batalkan suka pada profil ini'
                                                        : 'Suka profil ini'
                                                }
                                                className={`inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-4 py-2 font-display text-sm font-semibold transition ${
                                                    sudahSukaProfil
                                                        ? 'border border-terracotta bg-terracotta/10 text-terracotta hover:bg-terracotta/20'
                                                        : 'bg-primary text-white hover:bg-primary-dark'
                                                }`}
                                            >
                                                <svg
                                                    className="h-4 w-4"
                                                    viewBox="0 0 24 24"
                                                    fill={sudahSukaProfil ? 'currentColor' : 'none'}
                                                    stroke="currentColor"
                                                    strokeWidth={sudahSukaProfil ? 0 : 1.5}
                                                    aria-hidden="true"
                                                >
                                                    <path
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                        d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"
                                                    />
                                                </svg>
                                                {sudahSukaProfil ? 'Disukai' : 'Suka Profil'}
                                            </button>
                                        ) : (
                                            <Link
                                                href={route('login')}
                                                className="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 font-display text-sm font-semibold text-white transition hover:bg-primary-dark"
                                            >
                                                <svg
                                                    className="h-4 w-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    strokeWidth="1.5"
                                                    viewBox="0 0 24 24"
                                                    aria-hidden="true"
                                                >
                                                    <path
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                        d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"
                                                    />
                                                </svg>
                                                Suka Profil
                                            </Link>
                                        )
                                    )}
                                </div>
                            </div>
                        </div>

                        {/*
                            Statistik didorong ke kolom kanan pada layar lebar
                            supaya kartu terbaca sebagai satu blok identitas:
                            wajah + tombol di kiri, angka di kanan.

                            Dua wajah:
                             - koleksi : jumlah simpanan pribadi, jumlah suka
                                         pribadi, dan suka profil yang diterima
                             - publik  : resep terbit, suka profil, suka masakan
                        */}
                        <div className="grid w-full grid-cols-3 divide-x divide-border rounded-xl border border-border bg-linen/60 sm:w-auto sm:min-w-[300px]">
                            {(mode === 'koleksi'
                                ? [
                                      { angka: statsKoleksi?.disimpan ?? jumlahResep, label: 'disimpan' },
                                      { angka: statsKoleksi?.disukai ?? 0, label: 'disukai' },
                                      { angka: statsKoleksi?.sukaProfil ?? sukaProfilCount, label: 'suka profil' },
                                  ]
                                : [
                                      { angka: jumlahResep, label: 'resep terbit' },
                                      { angka: sukaProfilCount, label: 'suka profil' },
                                      { angka: sukaMasakanCount, label: 'suka masakan' },
                                  ]
                            ).map((s) => (
                                <div key={s.label} className="px-3 py-3 text-center">
                                    <p className="font-mono text-xl font-bold tabular-nums text-charcoal">
                                        {s.angka}
                                    </p>
                                    <p className="mt-1 text-[11px] uppercase tracking-wider text-muted">
                                        {s.label}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>

                    {pengguna.bio && (
                        <p className="mt-5 max-w-3xl font-body text-sm leading-relaxed text-muted">
                            {pengguna.bio}
                        </p>
                    )}

                    <div className="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-muted">
                        {pengguna.lokasi && (
                            <span className="inline-flex items-center gap-1.5">
                                <svg className="h-4 w-4 shrink-0" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                </svg>
                                {pengguna.lokasi}
                            </span>
                        )}

                        {sumberWebsite && (
                            <Link
                                href={sumberWebsite}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex items-center gap-1.5 underline-offset-4 transition hover:text-primary hover:underline"
                            >
                                <svg className="h-4 w-4 shrink-0" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757" />
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M14.5 8.5l-4.5 4.5a4.5 4.5 0 006.364 6.364l4.5-4.5" />
                                </svg>
                                {pengguna.website}
                            </Link>
                        )}

                        {rataRating !== null && (
                            <span className="inline-flex items-center">
                                <BintangRating
                                    nilai={rataRating}
                                    jumlah={jumlahUlasan}
                                    tampilkanAngka
                                />
                            </span>
                        )}
                    </div>

                    {rataRating === null && (
                        <p className="mt-4 font-body text-sm text-muted">
                            Belum ada rating.
                        </p>
                    )}
                </div>

                {/* ── Tabs ── */}
                <div className="mt-10 flex gap-1 overflow-x-auto border-b border-border pb-4">
                    {daftarTab.map((kunci) => (
                        <button
                            key={kunci}
                            type="button"
                            onClick={() => pindahTab(kunci)}
                            aria-current={tab === kunci ? 'page' : undefined}
                            className={`-mb-px shrink-0 px-3 py-2 text-sm font-medium transition border-b-2 ${
                                tab === kunci
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted hover:text-muted-dark'
                            }`}
                        >
                            {labelTab[kunci]}
                        </button>
                    ))}
                </div>

                {/* ── Grid resep — SATU blok, judulnya ikut tab aktif ── */}
                <div id="grid-resep" className="mt-10 scroll-mt-20 pb-28 overflow-x-hidden">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <h2 className="font-display text-xl font-bold tracking-tight text-charcoal">
                            {mode === 'koleksi'
                                ? `Koleksi ${labelTab[tab]} (${tampilan.length})`
                                : tab === 'unggulan'
                                  ? `Unggulan (${tampilan.length})`
                                  : `Resep terbit — ${labelTab[tab]} (${tampilan.length})`}
                        </h2>

                        {/*
                            Profil sendiri: etalase resep buatan (unggulan,
                            paling disukai, paling disimpan) hidup di Studio
                            Dapur — tautan pengingat supaya pemilik tahu
                            ke mana harus mencarinya.
                        */}
                        {mode === 'koleksi' && (
                            <Link
                                href={route('studio.index')}
                                className="inline-flex items-center gap-1.5 rounded-lg border border-border bg-surface px-3 py-1.5 text-xs font-semibold text-charcoal transition hover:bg-linen"
                            >
                                Resep buatan saya → Studio Dapur
                            </Link>
                        )}
                    </div>

                    {tampilan.length === 0 ? (
                        <p className="mt-4 font-body text-sm text-muted">
                            {mode === 'koleksi'
                                ? tab === 'disukai'
                                    ? 'Belum ada resep yang Anda sukai.'
                                    : 'Belum ada resep yang Anda simpan.'
                                : tab === 'unggulan'
                                  ? 'Belum ada resep unggulan.'
                                  : 'Belum membagikan resep yang sudah terbit.'}
                        </p>
                    ) : (
                        <div className="mt-6 grid w-full min-w-0 grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4">
                            {kartuTerlihat.map((r) => (
                                <RecipeCard
                                    key={r.id}
                                    recipe={r}
                                    // Mode koleksi berisi resep ORANG LAIN yang
                                    // disimpan/disukai — penulisnya wajib tampil
                                    // supaya pembaca tahu siapa koki di baliknya.
                                    // Mode publik tetap menyembunyikan karena semua
                                    // resep milik pemilik profil sendiri.
                                    tampilkanPenulis={mode !== 'koleksi'}
                                />
                            ))}
                        </div>
                    )}

                    {/* ── Paginasi klien ── */}
                    {totalHalaman > 1 && (
                        <nav
                            aria-label="Halaman resep"
                            className="mt-8 flex flex-col items-center gap-2"
                        >
                            <p className="font-mono text-xs text-muted">
                                Halaman {halamanAman} dari {totalHalaman} · {tampilan.length} resep
                            </p>

                            <ul className="inline-flex items-center gap-1 font-mono text-sm">
                                {Array.from({ length: totalHalaman }, (_, i) => i + 1).map((n) => (
                                    <li key={n}>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setHalaman(n);
                                                document
                                                    .getElementById('grid-resep')
                                                    ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                                            }}
                                            aria-current={halamanAman === n ? 'page' : undefined}
                                            className={`inline-flex h-8 min-w-[32px] items-center justify-center rounded-lg px-2 text-xs font-medium transition ${
                                                halamanAman === n
                                                    ? 'bg-primary text-white'
                                                    : 'text-muted hover:bg-linen hover:text-charcoal'
                                            }`}
                                        >
                                            {n}
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        </nav>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
