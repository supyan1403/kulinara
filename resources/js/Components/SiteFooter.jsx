import Logo from '@/Components/Logo';

/**
 * Kelompok tautan footer.
 *
 * Sebuah item tanpa `tautan` dan tanpa `route` adalah TULISAN BIASA, bukan
 * tautan. Dua baris pertama kelompok "Standar" sengaja dibuat begitu: dulu
 * keduanya menunjuk ke "#tentang" — menggulir ke FAQ — sehingga tampak seperti
 * dokumen padahal tidak ada halaman tujuannya sama sekali.
 */
const FOOTER_LINKS = [
    {
        title: 'Jelajahi',
        items: [
            { label: 'Beranda', tautan: '#' },
            { label: 'Resep', tautan: '#resep' },
            // "Peran" (#peran) dulu ada di sini — di luar beranda fungsi arah()
            // menerjemahkannya jadi /peran yang tidak ada rutenya (404).
        ],
    },
    {
        title: 'Akun',
        items: [
            { label: 'Masuk', href: 'login', route: true },
            { label: 'Daftar', href: 'register', route: true },
        ],
    },
    {
        title: 'Lainnya',
        items: [
            // `publik: true` — kredit foto wajib terbaca tanpa masuk akun,
            // karena halamannya memenuhi syarat lisensi CC BY-SA.
            { label: 'Kredit Foto', href: 'kredit-foto', route: true, publik: true },
        ],
    },
];

/**
 * Kaki halaman. Dipakai sama persis oleh beranda, direktori /resep, dan
 * halaman detail resep.
 */
export default function SiteFooter({ canLogin, canRegister, beranda = false }) {
    const arah = (tautan) => (beranda ? tautan : tautan === '#' ? '/' : `/${tautan}`);

    return (
        <footer className="border-t border-border bg-linen">
            <div className="mx-auto max-w-shell px-4 py-12 sm:px-6 lg:px-8">
                <div className="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="lg:col-span-1">
                        <div className="flex items-center gap-2">
                            <Logo className="h-8 w-8" />
                            <span className="text-lg font-extrabold tracking-tight text-primary">
                                kulinara<span className="text-terracotta">.</span>
                            </span>
                        </div>
                        <p className="mt-3 font-body text-sm leading-relaxed text-muted">
                            Direktori resep Nusantara, lengkap dengan perkiraan
                            kalori dan makronutrisi per porsi.
                        </p>
                    </div>

                    {FOOTER_LINKS.map((group) => (
                        <div key={group.title}>
                            <h3 className="font-mono text-xs font-semibold uppercase tracking-wider text-charcoal">
                                {group.title}
                            </h3>
                            <ul className="mt-3 space-y-2">
                                {group.items.map((item) => (
                                    <li key={item.label}>
                                        {item.route ? (
                                            item.publik || canLogin || canRegister ? (
                                                <a
                                                    href={route(item.href)}
                                                    className="font-body text-sm text-muted transition hover:text-primary"
                                                >
                                                    {item.label}
                                                </a>
                                            ) : null
                                        ) : item.tautan ? (
                                            <a
                                                href={arah(item.tautan)}
                                                className="font-body text-sm text-muted transition hover:text-primary"
                                            >
                                                {item.label}
                                            </a>
                                        ) : (
                                            <span className="font-body text-sm text-muted">
                                                {item.label}
                                            </span>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </div>

                <div className="mt-10 border-t border-border pt-6">
                    <p className="font-mono text-[11px] text-muted">
                        © {new Date().getFullYear()} Kulinara
                    </p>
                </div>
            </div>
        </footer>
    );
}
