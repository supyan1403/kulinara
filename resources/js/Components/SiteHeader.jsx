import AvatarInisial from '@/Components/AvatarInisial';
import Dropdown from '@/Components/Dropdown';
import {
    IconChevronDown,
    IconLogout,
    IconUser,
} from '@/Components/Icons';
import Logo from '@/Components/Logo';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { Link, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

// Tautan ruang kerja untuk pengguna yang sudah masuk. Daftar ini dulu hidup
// di AuthenticatedLayout, sehingga navbar berganti bentuk begitu pengguna
// berpindah dari /dashboard ke /resep: /resep memakai SiteHeader (tipe publik),
// bukan layout aplikasi. Hasilnya "Telusuri Resep" yang diklik berubah jadi
// pil hijau dan Dashboard/Studio Dapur menghilang. Sekarang keduanya memakai
// bilah yang sama dan hanya penanda aktifnya yang bergeser.
const NAV_MASUK = [
    // Untuk Pengguna, halaman ini bernama "Beranda": ia bukan dasbor kerja
    // melainkan pintu masuk personal — koleksi miliknya sendiri.
    { label: 'Dashboard', rute: 'dashboard', labelPengguna: 'Beranda' },
    // Telusuri didahulukan atas Dashboard secara konsep: bagi Pengguna,
    // menelusuri katalog itulah kegiatan utamanya, bukan membaca statistik.
    { label: 'Telusuri Resep', rute: 'resep.index' },
    // Tersimpan terbuka untuk semua peran, termasuk Pengguna — ini
    // satu-satunya ruang pribadi yang dimiliki pembaca.
    { label: 'Tersimpan', rute: 'simpanan.index' },
    { label: 'Cari', rute: 'cari.index' },
];

/**
 * Bilah navigasi atas — satu-satunya di aplikasi ini.
 *
 * Di beranda, `beranda` mengubah cara navbar menempel. Di sana hero dimulai
 * dari baris paling atas dan mengalir ke balik navbar, jadi navbar harus
 * `fixed` — kalau tetap `sticky`, ia ikut mendorong hero turun 64px, dan
 * ruang kosong itu justru yang membuat hero terasa pendek.
 *
 * Tamu di beranda tidak lagi melihat tautan apa pun di bilah ini: hanya logo
 * di kiri dan Masuk/Daftar di kanan. Tautan "Beranda"/"Resep" dulu hanya
 * jangkar ke section di halaman yang sama, dan bilah yang penuh membuat
 * etalase terbaca sebagai halaman dalam.
 *
 * Latar di beranda diturunkan ke 70% dan blur-nya dinaikkan, tapi tidak pernah
 * dibuat benar-benar transparan: teks charcoal di sini akan melintasi pita
 * hijau tua pada #resep dan #faq, dan di atas latar bening tulisannya hilang.
 * Krem 70% di atas hijau menghasilkan sage terang dengan kontras sekitar
 * 7.6:1 terhadap charcoal — masih nyaman dibaca. Di posisi teratas yang ada
 * di belakangnya krem polos, sehingga navbar tidak terbaca sebagai elemen
 * terpisah dari hero.
 *
 * Di luar beranda semuanya dibiarkan persis seperti sebelumnya: `sticky`
 * dengan latar pekat. Mengubahnya ke `fixed` tanpa pembeda akan menghapus
 * 64px ruang atas di setiap halaman dan menarik konten ke bawah navbar.
 */
export default function SiteHeader({ user, canLogin, canRegister, beranda = false, overlay = false }) {
    const { auth } = usePage().props;
    const permissions = auth?.permissions ?? {};
    const [menuTerbuka, setMenuTerbuka] = useState(false);
    const tombolMenuRef = useRef(null);

    /**
     * Menu seluler mengunci halaman di belakangnya.
     *
     * Panel ini `fixed` supaya konten utama tidak bergeser saat dibuka, tapi
     * tanpa penguncian gulir halaman belakang tetap berjalan: menggeser daftar
     * di dalam menu ikut menggeser halaman di baliknya. Efek ini juga menutup
     * menu pada Escape dan memindahkan fokus kembali ke tombol hamburger.
     *
     * Gulir dipulihkan pada cleanup — termasuk saat komponen dilepas dalam
     * keadaan terbuka — jadi `overflow: hidden` tidak pernah tertinggal.
     */
    useEffect(() => {
        if (!menuTerbuka) return;

        const gulirSebelumnya = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        const saatTombol = (e) => {
            if (e.key === 'Escape') setMenuTerbuka(false);
        };

        document.addEventListener('keydown', saatTombol);

        return () => {
            document.body.style.overflow = gulirSebelumnya;
            document.removeEventListener('keydown', saatTombol);
        };
    }, [menuTerbuka]);

    const tutupMenu = () => {
        setMenuTerbuka(false);
        tombolMenuRef.current?.focus();
    };

    const tautanRuangKerja = [
        ...NAV_MASUK,
        ...(permissions['access-studio']
            ? [
                  {
                      label: 'Studio Dapur',
                      rute: 'studio.index',
                  },
              ]
            : []),
        ...(permissions['access-panel']
            ? [
                  { label: 'Panel Pengelola', rute: 'panel.users' },
                  { label: 'Kurasi Resep', rute: 'kurasi.index' },
              ]
            : []),
    ];

    const sesuaikanLabel = (item) =>
        user?.role === 'pengguna' ? (item.labelPengguna ?? item.label) : item.label;

    // `route().current()` tersedia setelah Ziggy memuat rutenya (@routes di
    // app.blade.php). Dipanggil di dalam komponen, bukan di modul, supaya
    // tidak bergantung pada urutan pemuatan skrip.
    const sedangDi = (namaRute) =>
        typeof route === 'function' && route().current(namaRute);

    // Susunan kelas bilah disusun sebagai tiga potongan terpisah, bukan satu
    // blok bercabang dalam. Alasannya: hanya warna latar yang berbeda antara
    // beranda dan halaman dalam — sisanya (kelengkungan, bayangan, cara
    // menempel) ditentukan oleh satu hal saja, `beranda` atau `menuTerbuka`.
    // Menyatukannya dalam satu ekspresi bercabang membuat tiap kombinasi harus
    // ditulis manual dan mudah ada yang terlewat saat diubah.
    //
    // Cara menempel: beranda `fixed` supaya hero mengalir ke balik bilah;
    // halaman dalam `sticky` supaya tidak menghapus 64px ruang atas.
    // `overlay` menempati jalan beranda: dipakai halaman dalam yang HERO-nya
    // menyentuh puncak viewport (mis. profil publik) — cover harus utuh dari
    // baris pertama, jadi bilah tidak boleh mengambil 64px ruang.
    const [scrolled, setScrolled] = useState(false);

    useEffect(() => {
        if (beranda) {
            const handleScroll = () => {
                const heroHeight = window.innerHeight;
                const scrollY = window.scrollY;
                // Mulai transisi saat scroll melewati 1/4 hero (agar smooth sebelum section berikutnya)
                const shouldBlur = scrollY > heroHeight * 0.25;
                setScrolled(shouldBlur);
            };

            window.addEventListener('scroll', handleScroll);
            // Panggil sekali di mount untuk posisi awal
            handleScroll();

            return () => window.removeEventListener('scroll', handleScroll);
        }

        if (overlay) {
            // Bilah overlay telanjang di puncak, baru berubah jadi baris cream
            // setelah gambar sampul tersentuh scroll.
            const handleOverlayScroll = () => setScrolled(window.scrollY > 40);

            window.addEventListener('scroll', handleOverlayScroll);
            handleOverlayScroll();

            return () => window.removeEventListener('scroll', handleOverlayScroll);
        }

        // Halaman dalam biasa: navbar langsung blur (tidak ada transisi scroll)
        setScrolled(true);
    }, [beranda, overlay]);

    // `scrolled` di dua jalur atas menentukan bentuk DAN latar; di halaman
    // dalam biasa ia selalu true sejak awal sehingga kelasnya tak berubah.
    const telanjang = (beranda || overlay) && !scrolled && !menuTerbuka;

    const kelasTempel = beranda || overlay ? 'fixed inset-x-0 top-0' : 'sticky top-0';

    // Bentuk: saat menu tertutup DAN bilah sudah "berkartu" (telah discroll,
    // atau memang halaman dalam) sudut bawahnya membulat penuh dan diberi
    // bayangan. Saat masih menempel di atas hero (beranda, belum discroll),
    // sudut diratakan dan bayangan dilepas supaya bilah menyatu dengan hero —
    // garis pemisah hanya muncul setelah pengunjung mulai menggulir. Saat menu
    // terbuka sudut diratakan supaya bilah dan panel menyambung jadi satu
    // permukaan, dan bayangan dilepas karena ia akan menggambar garis gelap
    // tepat di sambungan yang sedang diratakan.
    const kelasBentuk = menuTerbuka
        ? 'rounded-b-none'
        : telanjang && beranda
            ? 'rounded-b-none'
            : 'rounded-b-2xl shadow-card';

    // Warna latar navbar:
    // - Transparan hanya saat menyapu hero BERANDA tanpa scroll
    // - Di profil publik (`overlay`) baris teratas tetap cream blur — teks nav
    //   harus terbaca di atas foto sampul apa pun warnanya
    // - Halaman dalam biasa: cream blur sejak awal
    // - Menu terbuka selalu bg-warm-cream penuh
    const kelasLatar = menuTerbuka
        ? 'bg-warm-cream backdrop-blur-xl'
        : beranda && telanjang
            ? 'bg-transparent backdrop-blur-none'
            : 'bg-warm-cream/80 backdrop-blur-xl';

    return (
        <header
            className={`z-40 transition-all duration-300 ease-out motion-reduce:transition-none ${kelasTempel} ${kelasBentuk} ${kelasLatar}`}
        >
            <div className="mx-auto flex h-16 max-w-shell items-center justify-between px-4 sm:px-6 lg:px-8">
                <div className="flex min-w-0 items-center">
                    <Link href="/" className="flex shrink-0 items-center gap-2">
                        <Logo className="h-8 w-8" />
                        <span className="text-lg font-extrabold tracking-tight text-primary">
                            kulinara<span className="text-terracotta">.</span>
                        </span>
                    </Link>

                    {user ? (
                        <nav className="hidden space-x-5 md:-my-px md:ms-6 md:flex">
                            {tautanRuangKerja.map((item) => (
                                <NavLink
                                    key={item.rute}
                                    href={route(item.rute)}
                                    active={sedangDi(item.rute)}
                                >
                                    {sesuaikanLabel(item)}
                                </NavLink>
                            ))}
                        </nav>
                    ) : (
                        <nav className="hidden space-x-5 md:-my-px md:ms-6 md:flex">
                            <NavLink
                                href={route('home')}
                                active={sedangDi('home')}
                            >
                                Beranda
                            </NavLink>
                            <NavLink
                                href={route('resep.index')}
                                active={sedangDi('resep.index')}
                            >
                                Resep
                            </NavLink>
                        </nav>
                    )}
                </div>

                <div className="flex shrink-0 items-center gap-2">
                    {user ? (
                        <div className="hidden items-center md:flex">
                            <Dropdown>
                                <Dropdown.Trigger>
                                    {/*
                                        Pemicu ini dulu sekadar kotak berisi
                                        nama dan chevron. Peran menempel di
                                        luarnya sebagai badge terpisah, sehingga
                                        terbaca sebagai dua elemen berjauhan.
                                        Sekarang keduanya duduk dalam satu
                                        tombol: inisial sebagai wajah, nama di
                                        atas, peran kecil di bawahnya.

                                        Radius `xl` menyamai tombol hamburger —
                                        keduanya bersebelahan di layar sempit,
                                        jadi perbedaan satu tingkat terlihat.
                                    */}
                                    <button
                                        type="button"
                                        aria-label={`Menu akun ${user.name}`}
                                        className="inline-flex items-center gap-2.5 rounded-xl border border-border bg-surface py-1.5 pe-2.5 ps-1.5 text-start text-charcoal transition hover:border-primary/30 hover:bg-linen focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-warm-cream"
                                    >
                                        <AvatarInisial
                                            nama={user.name}
                                            foto={user.avatar_path}
                                            ukuran="sm"
                                        />

                                        <span className="hidden min-w-0 lg:block">
                                            <span className="block max-w-[9rem] truncate font-display text-sm font-semibold leading-tight">
                                                {user.name}
                                            </span>
                                            <span className="block text-[11px] font-medium leading-tight text-muted">
                                                {user.role_label}
                                            </span>
                                        </span>

                                        <IconChevronDown className="h-4 w-4 shrink-0 text-muted" />
                                    </button>
                                </Dropdown.Trigger>

                                <Dropdown.Content align="right" width="64">
                                    {/* Kepala menu: identitas yang tidak muat
                                        di tombol — nama utuh dan email. Tanpa
                                        ini pengguna harus membuka Profil hanya
                                        untuk memastikan akun mana yang aktif. */}
                                    <div className="flex items-center gap-3 px-4 py-3">
                                        <AvatarInisial
                                            nama={user.name}
                                            foto={user.avatar_path}
                                            ukuran="md"
                                        />
                                        <div className="min-w-0">
                                            <p className="truncate font-display text-sm font-semibold text-charcoal">
                                                {user.name}
                                            </p>
                                            <p className="truncate font-body text-xs text-muted">
                                                {user.email}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="border-t border-border" />

                                    <div className="py-1">
                                        {/* Hanya aksi akun di sini. "Resep
                                            Tersimpan" dan "Studio Dapur" dulu
                                            ikut ditampilkan, padahal keduanya
                                            sudah selalu terlihat di bilah
                                            navigasi — mengulanginya hanya
                                            membuat menu ini tampak seperti
                                            daftar tautan situs, bukan menu
                                            akun. Navigasi tetap di nav; menu
                                            ini menjawab "siapa saya". */}
                                    </div>

                                    <div className="py-1">
                                        <Dropdown.Link
                                            href={user.username ? route('profil.show', user.username) : route('profile.edit')}
                                            className="flex items-center gap-2.5"
                                        >
                                            <IconUser className="h-4 w-4 text-muted" />
                                            Profil Saya
                                        </Dropdown.Link>
                                    </div>

                                    <div className="py-1">
                                        <Dropdown.Link
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                            className="flex items-center gap-2.5 text-terracotta hover:bg-terracotta/10 focus:bg-terracotta/10"
                                        >
                                            <IconLogout className="h-4 w-4" />
                                            Keluar
                                        </Dropdown.Link>
                                    </div>
                                </Dropdown.Content>
                            </Dropdown>
                        </div>
                    ) : (
                        <>
                            {canLogin && (
                                <Link
                                    href={route('login')}
                                    className="rounded-lg px-3 py-2 text-sm font-semibold text-charcoal transition hover:bg-linen"
                                >
                                    Masuk
                                </Link>
                            )}
                            {canRegister && (
                                <Link
                                    href={route('register')}
                                    className="hidden rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-dark sm:inline-flex"
                                >
                                    Daftar
                                </Link>
                            )}
                        </>
                    )}

                    {/* Hamburger di bawah `md` untuk navigasi seluler */}
                    <div className="-me-2 flex items-center md:hidden">
                        <button
                            ref={tombolMenuRef}
                            type="button"
                            onClick={() => (menuTerbuka ? tutupMenu() : setMenuTerbuka(true))}
                            aria-expanded={menuTerbuka}
                            aria-controls="menu-seluler"
                            aria-label={menuTerbuka ? 'Tutup menu' : 'Buka menu'}
                            className={`inline-flex items-center justify-center rounded-xl p-2 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary ${
                                menuTerbuka
                                    ? 'text-charcoal'
                                    : 'text-muted hover:bg-linen hover:text-charcoal'
                            }`}
                        >
                            {/* Ikon ikut berputar 90° saat menu terbuka.
                                `transition-transform` pada `<svg>` membuat
                                pergantian garis hamburger → silang terbaca
                                sebagai gerakan, bukan lompatan. Guard
                                `motion-reduce` menghormati pengaturan OS. */}
                            <svg
                                className={`h-6 w-6 transition-transform duration-200 ease-out motion-reduce:transition-none ${
                                    menuTerbuka ? 'rotate-90' : ''
                                }`}
                                stroke="currentColor"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    className={menuTerbuka ? 'hidden' : 'inline-flex'}
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                                <path
                                    className={menuTerbuka ? 'inline-flex' : 'hidden'}
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            {menuTerbuka && (
                <>
                    {/* Latar gelap di bawah bilah */}
                    <button
                        type="button"
                        aria-hidden="true"
                        tabIndex={-1}
                        onClick={tutupMenu}
                        className="fixed inset-x-0 bottom-0 top-16 z-20 cursor-default bg-charcoal/50 backdrop-blur-md animate-[backdrop-masuk_180ms_ease-out] motion-reduce:animate-none md:hidden"
                    />

                    <div
                        id="menu-seluler"
                        role="dialog"
                        aria-modal="true"
                        aria-label="Menu navigasi"
                        className="fixed inset-x-0 top-16 z-30 max-h-[calc(100vh-4rem)] origin-top overflow-y-auto rounded-b-2xl bg-warm-cream animate-[menu-masuk_180ms_ease-out] motion-reduce:animate-none md:hidden"
                    >
                        <div className="space-y-0.5 pb-3 pt-2">
                            {user ? (
                                tautanRuangKerja.map((item) => (
                                    <ResponsiveNavLink
                                        key={item.rute}
                                        href={route(item.rute)}
                                        active={sedangDi(item.rute)}
                                        onClick={tutupMenu}
                                    >
                                        {sesuaikanLabel(item)}
                                    </ResponsiveNavLink>
                                ))
                            ) : (
                                <>
                                    <ResponsiveNavLink
                                        href={route('home')}
                                        active={sedangDi('home')}
                                        onClick={tutupMenu}
                                    >
                                        Beranda
                                    </ResponsiveNavLink>
                                    <ResponsiveNavLink
                                        href={route('resep.index')}
                                        active={sedangDi('resep.index')}
                                        onClick={tutupMenu}
                                    >
                                        Resep
                                    </ResponsiveNavLink>
                                </>
                            )}
                        </div>

                        {user ? (
                            <div className="border-t border-border pb-2 pt-4">
                                <div className="flex items-center gap-3 px-4">
                                    <AvatarInisial
                                        nama={user.name}
                                        foto={user.avatar_path}
                                        ukuran="md"
                                    />
                                    <div className="min-w-0">
                                        <div className="truncate text-base font-medium text-charcoal">
                                            {user.name}
                                        </div>
                                        <div className="truncate text-sm font-medium text-muted">
                                            {user.email}
                                        </div>
                                    </div>
                                </div>

                                <div className="mt-2 space-y-0.5">
                                    <ResponsiveNavLink
                                        href={user.username ? route('profil.show', user.username) : route('profile.edit')}
                                        onClick={tutupMenu}
                                    >
                                        Profil Saya
                                    </ResponsiveNavLink>
                                    <ResponsiveNavLink
                                        method="post"
                                        href={route('logout')}
                                        as="button"
                                        onClick={tutupMenu}
                                        className="text-terracotta hover:bg-terracotta/10 focus:bg-terracotta/10"
                                    >
                                        Keluar
                                    </ResponsiveNavLink>
                                </div>
                            </div>
                        ) : (
                            (canLogin || canRegister) && (
                                <div className="space-y-2 border-t border-border p-4">
                                    {canLogin && (
                                        <Link
                                            href={route('login')}
                                            onClick={tutupMenu}
                                            className="block w-full rounded-xl border border-border bg-surface px-4 py-2.5 text-center text-sm font-semibold text-charcoal transition hover:bg-linen"
                                        >
                                            Masuk
                                        </Link>
                                    )}
                                    {canRegister && (
                                        <Link
                                            href={route('register')}
                                            onClick={tutupMenu}
                                            className="block w-full rounded-xl bg-primary px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-primary-dark"
                                        >
                                            Daftar
                                        </Link>
                                    )}
                                </div>
                            )
                        )}
                    </div>
                </>
            )}
        </header>
    );
}
