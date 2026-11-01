import { IconBookmark, IconClock, IconSearch } from '@/Components/Icons';
import RecipeCard from '@/Components/RecipeCard';
import Reveal from '@/Components/Reveal';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';

/**
 * Halaman pendaratan setelah masuk.
 *
 * Isinya MEMANG MILIK PENGGUNA — resep yang ia simpan — bukan salinan
 * katalog /resep. Dua halaman ini sengaja berisi data yang tidak bisa
 * keduanya: /resep menampilkan semua resep terbit; /dashboard hanya yang
 * Anda tandai. Hanya komponen kartunya (RecipeCard) yang sama.
 *
 * Tiga bagian menyusun halaman ini, dari yang paling pribadi ke yang paling
 * terbuka:
 *
 *   1. Ringkasan — berapa yang tersimpan, kategori kesukaannya, dan kapan
 *      terakhir menandai sesuatu. Angkanya datang dari server (prop
 *      `ringkasan`) karena dihitung dari SELURUH simpanan, bukan 8 kartu yang
 *      tampil. Ketiganya fakta simpanan — halaman ini sengaja TIDAK mengklaim
 *      apa pun tentang memasak, karena menyimpan resep belum berarti ia
 *      memasaknya, dan aplikasi belum mencatat aktivitas memasak.
 *   2. Koleksi — 4 kartu tersimpan terbaru sebagai pintu masuk cepat. Sisanya
 *      tinggal di /simpanan; menampilkan delapan di sini hanya mengulang
 *      halaman itu dan mendorong rekomendasi jauh ke bawah.
 *   3. Rekomendasi — resep tayang yang belum ia simpan. Bagian ini yang
 *      memberi beranda alasan untuk dibuka lagi.
 *
 * Deskripsi peran dulu dipakai dua kali di halaman ini — sekali di sapaan,
 * sekali lagi di panel "Apa berikutnya?" — sehingga pengguna membaca kalimat
 * yang sama persis dua kali. Kalimatnya kini hanya muncul sekali.
 */

const sapaanWaktu = () => {
    const jam = new Date().getHours();

    if (jam < 11) return 'Selamat pagi';
    if (jam < 15) return 'Selamat siang';
    if (jam < 19) return 'Selamat sore';

    return 'Selamat malam';
};

/**
 * Jarak waktu dalam bahasa manusia: "baru saja", "2 jam lalu", "3 hari lalu".
 *
 * Dihitung dari waktu browser, bukan server, supaya pengguna di zona waktu
 * lain tidak melihat "besok" untuk sesuatu yang baru ia lakukan.
 */
const sejakKapan = (iso) => {
    if (!iso) return null;

    const selisihMenit = Math.floor((Date.now() - new Date(iso).getTime()) / 60000);

    if (selisihMenit < 1) return 'baru saja';
    if (selisihMenit < 60) return `${selisihMenit} menit lalu`;

    const jam = Math.floor(selisihMenit / 60);
    if (jam < 24) return `${jam} jam lalu`;

    const hari = Math.floor(jam / 24);
    if (hari === 1) return 'kemarin';
    if (hari < 30) return `${hari} hari lalu`;

    const bulan = Math.floor(hari / 30);
    if (bulan < 12) return `${bulan} bulan lalu`;

    return `${Math.floor(bulan / 12)} tahun lalu`;
};


export default function Dashboard({
    jumlahTersimpan = 0,
    jumlahResep = 0,
    tersimpan = [],
    ringkasan = {},
    rekomendasi = [],
}) {
    const user = usePage().props.auth.user;
    const role = user?.role;
    const judulHalaman = role === 'pengguna' ? 'Beranda' : 'Dashboard';

    const deskripsi = {
        pengguna:
            'Koleksi resep yang Anda tandai untuk dimasak — semua tersedia di sini kapanpun Anda membuka kembali.',
        pengelola: 'Tinjau resep dari para pengguna dan kelola peran pengguna.',
    };

    const adaIsi = jumlahTersimpan > 0;

    // Penikmat yang baru mendaftar tidak punya apa-apa untuk diringkas: ketiga
    // kartu akan berbunyi "—" dan dua blok di bawahnya kosong. Menampilkan
    // kerangka kosong lebih dulu membuat halaman ini terbaca sebagai tempat
    // yang belum jadi, padahal yang sebenarnya dibutuhkan adalah satu langkah
    // berikutnya.
    const penggunaKosong = role === 'pengguna' && !adaIsi;

    // "Dapur sudah dibuka" = pengguna sudah membuat walau satu resep di
    // Studio. Ajakan membuka dapur (panelStudio) hanya relevan selama ini
    // masih nol — begitu ada resep buatannya, pintunya memang sudah terbuka
    // dan panel tidak perlu muncul lagi.
    const bukaDapur = jumlahResep > 0;

    // Kartu ringkasan. Nilai kosong ditulis sebagai "—", bukan 0: nol berarti
    // "Anda punya nol", sedangkan tanda pisah berarti "belum ada datanya".
    const statistik = [
        {
            label: 'Resep tersimpan',
            nilai: adaIsi ? jumlahTersimpan : '—',
            ikon: IconBookmark,
            href: route('simpanan.index'),
        },
        {
            label: 'Kategori favorit',
            nilai: ringkasan?.kategoriFavorit ?? '—',
            ikon: null,
            href: null,
        },
        {
            label: 'Terakhir disimpan',
            nilai: sejakKapan(ringkasan?.terakhirDisimpan) ?? '—',
            ikon: IconClock,
            href: null,
        },
    ];

    /**
     * Blok penawaran Studio Dapur.
     *
     * Dulu satu-satunya jalan ke Studio adalah menggulir melewati dua bagian
     * kosong di bawah. Untuk Penikmat tanpa simpanan, ia dipindah ke atas:
     * janji "siapapun bisa buka dapur tanpa ribet" hanya berguna kalau
     * pintunya terlihat saat halaman dibuka.
     */
    const panelStudio = (
        <section className="rounded-2xl border border-border bg-surface p-7 shadow-card sm:p-9">
            <div className="flex flex-wrap items-center justify-between gap-7">
                <div className="min-w-0">
                    <span className="inline-flex items-center gap-2 rounded-full bg-linen px-3 py-1 font-display text-[11px] font-semibold uppercase tracking-[0.03em] text-muted ring-1 ring-inset ring-border">
                        <span className="h-1.5 w-1.5 rounded-full bg-mint-spark" />
                        Studio Dapur
                    </span>

                    <h2 className="mt-4 font-display text-2xl font-bold tracking-tight text-charcoal">
                        Siapapun boleh membuka dapurnya sendiri
                    </h2>
                    <p className="mt-3 max-w-xl font-body text-sm leading-relaxed text-muted">
                        Susun resep, lengkapi bahan dan langkah, lalu ajukan untuk
                        ditinjau Pengelola. Tanpa ribet — mulai dari satu resep
                        pertama Anda.
                    </p>
                </div>

                <Link
                    href={route('studio.index')}
                    className="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-lg bg-primary px-7 py-3.5 font-display text-sm font-semibold text-white shadow-raised transition hover:bg-primary-dark"
                >
                    Buka Studio Dapur
                </Link>
            </div>
        </section>
    );

    return (
        <AuthenticatedLayout>
            <Head title={judulHalaman} />

            <div className="min-h-screen bg-warm-cream pb-16">
                <div className="mx-auto max-w-shell px-4 py-8 sm:px-6 lg:px-8">
                    {/* ───────── Sapaan ───────── */}
                    <div className="mb-8">
                        <span className="inline-flex items-center gap-2 rounded-full bg-surface px-3 py-1 font-display text-[11px] font-semibold uppercase tracking-[0.03em] text-muted ring-1 ring-inset ring-border">
                            <span className="h-1.5 w-1.5 rounded-full bg-mint-spark" />
                            {judulHalaman}
                        </span>

                        <h1 className="mt-4 font-display text-3xl font-bold tracking-tight text-charcoal sm:text-4xl">
                            {sapaanWaktu()}, {user?.name?.split(' ')[0] ?? 'Teman-teman'}.
                        </h1>
                        <p className="mt-2 max-w-2xl font-body text-sm leading-relaxed text-muted sm:text-base">
                            {deskripsi[role] ?? deskripsi.pengguna}
                        </p>
                    </div>

                    {/* ── Pengguna baru (dapur belum dibuka) ──
                        Panel Studio tampil di atas hanya selama pengguna belum
                        membuat resep sama sekali. Begitu dapurnya terbuka
                        (punya resep buatan), ajakan ini lenyap — pintu sudah
                        terbuka, tak perlu ditawari lagi. */}
                    {penggunaKosong && !bukaDapur && (
                        <div className="mb-10">{panelStudio}</div>
                    )}

                    {/* ───────── Ringkasan ───────── */}
                    {/* Disembunyikan saat kosong: tiga kartu "—" bukan
                        ringkasan, melainkan daftar hal yang belum terjadi. */}
                    {!penggunaKosong && (
                        <section className="mb-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {statistik.map(({ label, nilai, ikon: Ikon, href }) => {
                                const isi = (
                                    <>
                                        <div className="flex items-center gap-2">
                                            {Ikon && <Ikon className="h-4 w-4 text-primary" />}
                                            <p className="font-body text-sm font-medium text-muted">
                                                {label}
                                            </p>
                                        </div>
                                        <p className="mt-2 font-display text-2xl font-bold tabular-nums tracking-tight text-charcoal">
                                            {nilai}
                                        </p>
                                    </>
                                );

                                const kelas =
                                    'rounded-xl border border-border bg-surface px-5 py-4 shadow-card';

                                // Hanya kartu yang memang punya tujuan yang menjadi
                                // tautan. Kartu statistik yang bisa diklik tetapi
                                // membawa ke tempat yang tidak relevan lebih buruk
                                // daripada kartu biasa.
                                return href ? (
                                    <Link
                                        key={label}
                                        href={href}
                                        className={`${kelas} transition hover:-translate-y-0.5 hover:shadow-raised`}
                                    >
                                        {isi}
                                    </Link>
                                ) : (
                                    <div key={label} className={kelas}>
                                        {isi}
                                    </div>
                                );
                            })}
                        </section>
                    )}

                    {/* ───────── Koleksi Anda ───────── */}
                    <section>
                        <div className="flex flex-wrap items-end justify-between gap-4">
                            <div>
                                <h2 className="font-mono text-xs uppercase tracking-wider text-muted">
                                    Koleksi Anda
                                </h2>
                                <p className="mt-1 font-body text-2xl font-bold tracking-tight text-charcoal">
                                    Resep yang Anda tandai
                                </p>
                            </div>

                            {tersimpan.length > 0 && (
                                <Link
                                    href={route('simpanan.index')}
                                    className="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 font-body text-sm font-semibold text-primary transition hover:text-primary-dark"
                                >
                                    Lihat semua <span aria-hidden="true">→</span>
                                </Link>
                            )}
                        </div>

                        {tersimpan.length === 0 ? (
                            <div className="mt-5 rounded-2xl border border-dashed border-border bg-surface px-6 py-12 text-center">
                                <IconSearch className="mx-auto h-10 w-10 text-muted" />
                                <h3 className="mt-4 font-display text-base font-bold text-charcoal">
                                    Belum ada resep tersimpan
                                </h3>
                                <p className="mx-auto mt-1 max-w-md font-body text-sm leading-relaxed text-muted">
                                    Tandai resep dengan ikon bookmark saat menelusuri katalog.
                                    Semua resep yang Anda simpan akan muncul di sini untuk
                                    ditemukan kembali kapan saja.
                                </p>
                                <Link
                                    href={route('resep.index')}
                                    className="mt-5 inline-flex min-h-11 items-center justify-center rounded-lg bg-primary px-5 py-2 font-display text-sm font-semibold text-white transition hover:bg-primary-dark"
                                >
                                    Telusuri resep
                                </Link>
                            </div>
                        ) : (
                            <div className="mt-5 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                {tersimpan.slice(0, 4).map((recipe) => (
                                    <RecipeCard key={recipe.id} recipe={recipe} />
                                ))}
                            </div>
                        )}
                    </section>

                    {/* ───────── Rekomendasi ───────── */}
                    {rekomendasi.length > 0 && (
                        <section className="mt-14">
                            <div className="flex flex-wrap items-end justify-between gap-4">
                                <div>
                                    <h2 className="font-mono text-xs uppercase tracking-wider text-muted">
                                        Mungkin Anda suka
                                    </h2>
                                    <p className="mt-1 font-body text-2xl font-bold tracking-tight text-charcoal">
                                        {ringkasan?.kategoriFavorit
                                            ? `Lainnya di ${ringkasan.kategoriFavorit}`
                                            : 'Belum pernah Anda coba'}
                                    </p>
                                    <p className="mt-1 max-w-xl font-body text-sm leading-relaxed text-muted">
                                        {ringkasan?.kategoriFavorit
                                            ? 'Dipilih dari kategori yang paling sering Anda simpan.'
                                            : 'Resep terbaru dari dapur komunitas — tandai yang menarik.'}
                                    </p>
                                </div>
                            </div>

                            <div className="mt-5 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                {rekomendasi.map((recipe, i) => (
                                    <Reveal key={recipe.id} delay={i * 60}>
                                        <RecipeCard recipe={recipe} />
                                    </Reveal>
                                ))}
                            </div>
                        </section>
                    )}

                    {/* ───────── Jalur jadi Kreator ───────── */}
                    {/* Tampil hanya bila ada simpanan (pengguna aktif) TAPI
                        dapurnya belum dibuka — pengguna yang sudah punya resep
                        buatan tidak lagi ditawari "buka dapur". */}
                    {role === 'pengguna' && adaIsi && !bukaDapur && (
                        <div className="mt-4">{panelStudio}</div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
