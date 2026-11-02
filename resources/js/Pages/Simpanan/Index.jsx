import { IconBookmark } from '@/Components/Icons';
import RecipeCard from '@/Components/RecipeCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

/**
 * Koleksi resep yang ditandai pengguna.
 *
 * Memakai RecipeCard yang sama persis dengan yang dipakai direktori publik,
 * supaya kartu yang dilihat seseorang saat menelusuri dan yang ia temukan
 * kembali di sini tidak mungkin berbeda penampilannya.
 *
 * Dipaginasi di server (12 per halaman) — tanpa ini koleksi yang sudah
 * puluhan resep membuat halaman terlalu panjang ke bawah.
 */
export default function SimpananIndex({ resep, kategoriTersedia = [], paginasi = null, resepDisimpan = [] }) {
    const labelKategori = Object.fromEntries(
        kategoriTersedia.map((k) => [k.value, k.label]),
    );

    // Angka di judul memakai TOTAL, bukan isi halaman ini — "96 resep yang
    // Anda tandai" tetap benar walau yang tampil cuma 12 kartu.
    const jumlah = paginasi?.total ?? resep.length;

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h2 className="text-xl font-bold leading-tight tracking-tight text-charcoal">
                        Resep Tersimpan
                    </h2>
                    <p className="mt-1 font-body text-sm text-muted">
                        {jumlah === 0
                            ? 'Belum ada resep yang Anda tandai.'
                            : `${jumlah} resep yang Anda tandai agar mudah ditemukan lagi.`}
                    </p>
                </div>
            }
        >
            <Head title="Resep Tersimpan" />

            <div className="py-12">
                <div className="mx-auto max-w-shell sm:px-6 lg:px-8">
                    {jumlah === 0 ? (
                        <div className="rounded-2xl border border-dashed border-border bg-surface px-6 py-14 text-center">
                            <IconBookmark className="mx-auto h-10 w-10 text-muted" />
                            <h3 className="mt-4 text-base font-bold text-charcoal">
                                Belum ada resep tersimpan
                            </h3>
                            <p className="mx-auto mt-1 max-w-md font-body text-sm leading-relaxed text-muted">
                                Buka sebuah resep lalu tekan &ldquo;Simpan resep&rdquo;.
                                Simpanannya tersimpan di akun Anda, jadi bisa
                                diakses dari perangkat mana pun.
                            </p>
                            <div className="mt-5">
                                <Link
                                    href={route('resep.index')}
                                    className="inline-block rounded-lg bg-primary px-6 py-3 text-sm font-semibold text-white shadow-raised transition hover:bg-primary-dark"
                                >
                                    Telusuri resep
                                </Link>
                            </div>
                        </div>
                    ) : (
                        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            {resep.map((recipe) => (
                                <RecipeCard
                                    key={recipe.slug}
                                    recipe={recipe}
                                    labelKategori={labelKategori}
                                    disimpan={resepDisimpan.includes(recipe.id)}
                                />
                            ))}
                        </div>
                    )}

                    {/* ── Navigasi halaman ── */}
                    {paginasi && paginasi.terakhir > 1 && (
                        <nav
                            aria-label="Halaman resep tersimpan"
                            className="mt-10 flex flex-col items-center gap-2"
                        >
                            <p className="font-mono text-xs text-muted">
                                Halaman {paginasi.halaman_ini} dari {paginasi.terakhir} ·{' '}
                                {paginasi.total} resep tersimpan
                            </p>

                            <ul className="inline-flex items-center gap-1 font-mono text-sm">
                                {paginasi.tautan.map((t, i) => (
                                    <li key={i}>
                                        {t.url ? (
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    router.get(t.url, {}, { preserveScroll: true })
                                                }
                                                className={`inline-flex h-8 min-w-[32px] items-center justify-center rounded-lg px-2 text-xs font-medium transition ${
                                                    t.active
                                                        ? 'bg-primary text-white'
                                                        : 'text-muted hover:bg-linen hover:text-charcoal'
                                                }`}
                                                dangerouslySetInnerHTML={{
                                                    __html: labelPagination(t.label),
                                                }}
                                            />
                                        ) : (
                                            <span
                                                className="inline-flex h-8 min-w-[32px] items-center justify-center rounded-lg px-2 text-xs font-medium text-muted"
                                                dangerouslySetInnerHTML={{
                                                    __html: labelPagination(t.label),
                                                }}
                                            />
                                        )}
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

/** Pagination link label — decode entitas HTML yang dikirim Laravel. */
const labelPagination = (l) =>
    l.replace(/&laquo;/g, '«').replace(/&raquo;/g, '»').replace(/&hellip;/g, '...');
