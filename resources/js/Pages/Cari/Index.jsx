import AvatarInisial from '@/Components/AvatarInisial';
import { IconCheck, IconSearch } from '@/Components/Icons';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

/**
 * Halaman pencarian akun / profil kreator.
 *
 * Publik (bisa diakses tamu). Fokus pada profil Pengguna biasa — bukan
 * Pengelola — supaya hasilnya selalu mengarah ke "kreator yang bisa
 * Anda ikuti / sukai".
 *
 * Query string `akun=` memicu pencarian; pagination Inertia otomatis
 * mempertahankan query string saat pindah halaman.
 */
export default function CariIndex({ pengguna, kataCari }) {
    const { auth } = usePage().props;
    const [query, setQuery] = useState(kataCari || '');

    const daftar = useMemo(() => {
        const list = pengguna?.data || [];
        const q = query.trim().toLowerCase();

        if (!q) {
            return list;
        }

        return list.filter((u) => {
            const nama = (u.name || '').toLowerCase();
            const username = (u.username || '').toLowerCase();

            return nama.includes(q) || username.includes(q) || `@${username}`.includes(q);
        });
    }, [pengguna, query]);

    return (
        <AuthenticatedLayout>
            <Head title="Cari profil" />

            <main className="mx-auto max-w-shell px-4 py-12 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-xl">
                    <h1 className="font-display text-2xl font-bold tracking-tight text-charcoal">
                        {kataCari ? 'Hasil pencarian' : 'Pengguna Teratas'}
                    </h1>
                    <p className="mt-2 font-body text-sm text-muted">
                        {kataCari
                            ? 'Temukan akun Pengguna lain — klik nama untuk membuka profil.'
                            : 'Kreator teratas berdasarkan jumlah resep.'}
                    </p>

                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            const nilai = query.trim();
                            router.get(
                                route('cari.index'),
                                nilai ? { akun: nilai } : {},
                                { preserveState: true, preserveScroll: true },
                            );
                        }}
                        className="mt-6"
                    >
                        <label htmlFor="akun" className="sr-only">
                            Cari nama atau username
                        </label>
                        <div className="relative">
                            <IconSearch className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-muted" />
                            <input
                                id="akun"
                                type="search"
                                name="akun"
                                value={query}
                                onChange={(e) => setQuery(e.target.value)}
                                placeholder="Nama atau @username"
                                className="w-full rounded-xl border border-border bg-surface pl-10 pr-4 py-2.5 text-sm text-charcoal placeholder-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                autoComplete="off"
                            />
                        </div>
                    </form>
                </div>

                {/* ── Hasil ── */}
                {daftar.length === 0 ? (
                    <div className="mt-10 rounded-2xl border border-dashed border-border bg-surface px-6 py-14 text-center">
                        <IconSearch className="mx-auto h-8 w-8 text-muted" />
                        <h3 className="mt-3 text-base font-bold text-charcoal">
                            {query.trim()
                                ? 'Akun tidak ditemukan'
                                : 'Belum ada pengguna lain'}
                        </h3>
                        <p className="mt-1 font-body text-sm text-muted">
                            {query.trim()
                                ? 'Coba cek ejaan nama atau @username.'
                                : 'Saat ini belum ada kreator lain untuk ditampilkan.'}
                        </p>
                    </div>
                ) : (
                    <div className="mt-8">
                        <ul className="divide-y divide-border/80">
                            {daftar.map((u, idx) => {
                                if (!u.username) {
                                    return null;
                                }

                                const peringkat = !query.trim() ? (pengguna.from ?? 1) + idx : null;
                                const top = peringkat && peringkat <= 3;

                                return (
                                    <li key={u.id}>
                                        <Link
                                            href={route('profil.show', u.username)}
                                            className="flex items-center gap-4 px-2 py-3 transition hover:bg-linen/40 sm:px-0"
                                        >
                                            <AvatarInisial
                                                nama={u.name}
                                                foto={u.avatar_path}
                                                ukuran="md"
                                                className={top ? 'ring-2 ring-primary/40 ring-offset-2 ring-offset-warm-cream' : ''}
                                            />
                                            <div className="min-w-0 flex-1">
                                                <div className="flex items-center gap-2">
                                                    <span className="truncate font-display text-base font-bold text-charcoal">
                                                        {u.name}
                                                    </span>
                                                    {top && (
                                                        <span className="inline-flex items-center rounded-full bg-primary/10 px-1.5 py-0.5 font-mono text-[10px] font-semibold uppercase tracking-wide text-primary">
                                                            Top {peringkat}
                                                        </span>
                                                    )}
                                                    {u.verified && (
                                                        <span
                                                            title="Akun terverifikasi"
                                                            className="inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-sage-dark text-white"
                                                        >
                                                            <IconCheck className="h-2.5 w-2.5" />
                                                        </span>
                                                    )}
                                                </div>
                                                <p className="truncate font-mono text-sm text-muted">
                                                    @{u.username}
                                                </p>
                                            </div>
                                            <div className="shrink-0 text-right text-xs text-muted">
                                                <span className="font-mono font-semibold tabular-nums text-charcoal">
                                                    {u.reseps_count ?? u.jumlah_resep ?? 0}
                                                </span>{' '}
                                                resep
                                            </div>
                                        </Link>
                                    </li>
                                );
                            })}
                        </ul>
                    </div>
                )}

                {/* ── Pagination ── */}
                {pengguna.last_page > 1 && (
                    <nav aria-label="Halaman hasil pencarian" className="mt-10 flex justify-center">
                        <ul className="inline-flex items-center gap-1 font-mono text-sm">
                            {pengguna.links.map((link, i) => (
                                <li key={i}>
                                    {link.url ? (
                                        <Link
                                            href={link.url}
                                            preserveState
                                            preserveScroll
                                            className={`inline-flex h-8 min-w-[32px] items-center justify-center rounded-lg px-2 text-xs font-medium ${
                                                link.active
                                                    ? 'bg-primary text-white'
                                                    : 'text-muted hover:bg-linen hover:text-charcoal'
                                            }`}
                                            dangerouslySetInnerHTML={{
                                                __html: labelPagination(link.label),
                                            }}
                                        />
                                    ) : (
                                        <span
                                            className="inline-flex h-8 min-w-[32px] items-center justify-center rounded-lg px-2 text-xs font-medium text-muted"
                                            dangerouslySetInnerHTML={{
                                                __html: labelPagination(link.label),
                                            }}
                                        />
                                    )}
                                </li>
                            ))}
                        </ul>
                    </nav>
                )}
            </main>
        </AuthenticatedLayout>
    );
}

/** Pagination link label — decode entitas HTML yang dikirim Inertia. */
const labelPagination = (l) =>
    l.replace(/&laquo;/g, '«').replace(/&raquo;/g, '»').replace(/&hellip;/g, '…');
