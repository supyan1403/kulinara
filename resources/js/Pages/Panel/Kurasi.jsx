import { IconCheck, IconClock, IconPencil } from '@/Components/Icons';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

/**
 * Meja kerja kurasi — hanya untuk Pengelola.
 *
 * Dua daftar di halaman ini melayani dua masalah berbeda:
 *
 *   1. Antrean — pengajuan yang menunggu keputusan. Diurutkan dari yang
 *      paling lama menunggu, karena pengajuan yang tertimbun adalah cara
 *      paling umum sebuah platform konten kehilangan penulisnya.
 *   2. Perlu diperiksa — resep yang SUDAH tayang lalu diubah isinya.
 *      Suntingan berlaku langsung, jadi ini bukan gerbang; ini pengawasan.
 *
 * Pengelola sengaja tidak bisa menyunting isi resep di sini. Takaran
 * menyangkut hasil masakan orang, dan mengubahnya diam-diam membuat penulis
 * kehilangan jejak karyanya. Keputusannya hanya setujui atau tolak.
 */

const sejakKapan = (iso) => {
    if (!iso) return null;

    const menit = Math.floor((Date.now() - new Date(iso).getTime()) / 60000);

    if (menit < 1) return 'baru saja';
    if (menit < 60) return `${menit} menit lalu`;

    const jam = Math.floor(menit / 60);
    if (jam < 24) return `${jam} jam lalu`;

    const hari = Math.floor(jam / 24);
    if (hari === 1) return 'kemarin';
    if (hari < 30) return `${hari} hari lalu`;

    return `${Math.floor(hari / 30)} bulan lalu`;
};

function KartuKurasi({ resep, mode }) {
    const [catatan, setCatatan] = useState('');
    const [membukaTolak, setMembukaTolak] = useState(false);

    const kirim = (rute, data) => {
        router.post(route(rute, resep.id), data ?? {}, { preserveScroll: true });
    };

    const tolak = (e) => {
        e.preventDefault();
        kirim('kurasi.tolak', { catatan });
    };

    return (
        <article className="rounded-2xl border border-border bg-surface shadow-card">
            <div className="flex flex-wrap items-start justify-between gap-3 border-b border-border px-5 py-4">
                <div className="min-w-0">
                    <h3 className="font-display text-base font-bold text-charcoal">
                        {resep.judul}
                    </h3>
                    <p className="mt-0.5 font-body text-xs text-muted">
                        {resep.kategori} · {resep.waktu_masak_menit} mnt ·{' '}
                        {resep.porsi} porsi
                    </p>
                </div>

                <div className="text-end">
                    <p className="font-body text-xs font-semibold text-charcoal">
                        {resep.penulis}
                    </p>
                    <p className="font-mono text-[11px] text-muted">
                        {sejakKapan(
                            mode === 'antrean'
                                ? resep.diajukan_at
                                : resep.disunting_setelah_terbit_at,
                        )}
                    </p>
                </div>
            </div>

            <div className="space-y-4 px-5 py-4">
                <p className="font-body text-sm leading-relaxed text-muted">
                    {resep.deskripsi}
                </p>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p className="font-mono text-[11px] uppercase tracking-wider text-muted">
                            Bahan ({resep.bahan.length})
                        </p>
                        <ul className="mt-2 space-y-1">
                            {resep.bahan.slice(0, 6).map((b, i) => (
                                <li key={i} className="font-body text-xs text-charcoal">
                                    {b.nama} — {b.jumlah} {b.satuan}
                                </li>
                            ))}
                            {resep.bahan.length > 6 && (
                                <li className="font-body text-xs text-muted">
                                    +{resep.bahan.length - 6} bahan lainnya
                                </li>
                            )}
                        </ul>
                    </div>

                    <div>
                        <p className="font-mono text-[11px] uppercase tracking-wider text-muted">
                            Langkah ({resep.langkah.length})
                        </p>
                        <ol className="mt-2 space-y-1">
                            {resep.langkah.slice(0, 3).map((l, i) => (
                                <li key={i} className="font-body text-xs text-charcoal">
                                    <span className="text-muted">{i + 1}.</span> {l}
                                </li>
                            ))}
                            {resep.langkah.length > 3 && (
                                <li className="font-body text-xs text-muted">
                                    +{resep.langkah.length - 3} langkah lainnya
                                </li>
                            )}
                        </ol>
                    </div>
                </div>

                {resep.catatan_kurasi && (
                    <div className="rounded-lg border border-border bg-warm-cream px-3 py-2">
                        <p className="font-mono text-[11px] uppercase tracking-wider text-muted">
                            Catatan penolakan terakhir
                        </p>
                        <p className="mt-1 font-body text-xs text-charcoal">
                            {resep.catatan_kurasi}
                        </p>
                    </div>
                )}

                <Link
                    href={route('resep.show', resep.slug)}
                    className="inline-flex items-center gap-1.5 font-body text-xs font-semibold text-primary transition hover:text-primary-dark"
                >
                    Lihat halaman resep <span aria-hidden="true">→</span>
                </Link>
            </div>

            <div className="border-t border-border px-5 py-3">
                {mode === 'antrean' ? (
                    membukaTolak ? (
                        <form onSubmit={tolak} className="space-y-2">
                            <label
                                htmlFor={`catatan-${resep.id}`}
                                className="block font-body text-xs font-semibold text-charcoal"
                            >
                                Alasan penolakan (wajib, agar penulis tahu apa
                                yang harus diperbaiki)
                            </label>
                            <textarea
                                id={`catatan-${resep.id}`}
                                value={catatan}
                                onChange={(e) => setCatatan(e.target.value)}
                                rows={2}
                                required
                                minLength={10}
                                maxLength={500}
                                className="w-full rounded-lg border border-border bg-surface px-3 py-2 font-body text-sm text-charcoal focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                            />
                            <div className="flex flex-wrap gap-2">
                                <button
                                    type="submit"
                                    disabled={catatan.trim().length < 10}
                                    className="inline-flex min-h-9 items-center rounded-lg bg-terracotta px-4 py-1.5 font-display text-sm font-semibold text-white transition hover:bg-[#9c4526] disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    Kirim penolakan
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setMembukaTolak(false)}
                                    className="inline-flex min-h-9 items-center rounded-lg px-3 py-1.5 font-body text-sm font-semibold text-muted transition hover:text-charcoal"
                                >
                                    Batal
                                </button>
                            </div>
                        </form>
                    ) : (
                        <div className="flex flex-wrap gap-2">
                            <button
                                type="button"
                                onClick={() => kirim('kurasi.setujui')}
                                className="inline-flex min-h-9 items-center gap-1.5 rounded-lg bg-primary px-4 py-1.5 font-display text-sm font-semibold text-white transition hover:bg-primary-dark"
                            >
                                <IconCheck className="h-4 w-4" />
                                Setujui & tayangkan
                            </button>
                            <button
                                type="button"
                                onClick={() => setMembukaTolak(true)}
                                className="inline-flex min-h-9 items-center rounded-lg border border-border px-4 py-1.5 font-display text-sm font-semibold text-charcoal transition hover:border-terracotta hover:text-terracotta"
                            >
                                Tolak
                            </button>
                        </div>
                    )
                ) : (
                    <div className="flex flex-wrap gap-2">
                        <button
                            type="button"
                            onClick={() => kirim('kurasi.diperiksa')}
                            className="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-border px-4 py-1.5 font-display text-sm font-semibold text-charcoal transition hover:border-primary hover:text-primary"
                        >
                            <IconCheck className="h-4 w-4" />
                            Tandai sudah diperiksa
                        </button>
                    </div>
                )}
            </div>
        </article>
    );
}

export default function Kurasi({ antrean = [], disunting = [], statistik = {} }) {
    const { flash } = usePage().props;

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h2 className="text-xl font-bold leading-tight tracking-tight text-charcoal">
                        Kurasi Resep
                    </h2>
                    <p className="mt-1 font-body text-sm text-muted">
                        Tinjau resep yang diajukan Kreator sebelum tayang di direktori.
                    </p>
                </div>
            }
        >
            <Head title="Kurasi Resep" />

            <div className="py-10">
                <div className="mx-auto max-w-shell space-y-10 px-4 sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="rounded-xl border border-primary/20 bg-mint-light px-4 py-3 font-body text-sm text-primary">
                            {flash.success}
                        </div>
                    )}
                    {flash?.error && (
                        <div className="rounded-xl border border-terracotta/30 bg-terracotta/10 px-4 py-3 font-body text-sm text-[#9c4526]">
                            {flash.error}
                        </div>
                    )}

                    {/* ───────── Antrean ───────── */}
                    <section>
                        <div className="flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <h2 className="font-mono text-xs uppercase tracking-wider text-muted">
                                    Menunggu keputusan
                                </h2>
                                <p className="mt-1 font-display text-2xl font-bold tracking-tight text-charcoal">
                                    Antrean kurasi
                                </p>
                            </div>
                            <span className="inline-flex items-center gap-1.5 rounded-full bg-surface px-3 py-1 font-mono text-xs font-semibold text-charcoal ring-1 ring-inset ring-border">
                                <IconClock className="h-3.5 w-3.5 text-terracotta" />
                                {statistik?.menunggu ?? 0} menunggu
                            </span>
                        </div>

                        {antrean.length === 0 ? (
                            <div className="mt-5 rounded-2xl border border-dashed border-border bg-surface px-6 py-12 text-center">
                                <IconCheck className="mx-auto h-9 w-9 text-primary" />
                                <h3 className="mt-3 font-display text-base font-bold text-charcoal">
                                    Antrean kosong
                                </h3>
                                <p className="mx-auto mt-1 max-w-md font-body text-sm leading-relaxed text-muted">
                                    Tidak ada resep yang menunggu. Semua pengajuan
                                    Kreator sudah ditinjau.
                                </p>
                            </div>
                        ) : (
                            <div className="mt-5 grid gap-5 lg:grid-cols-2">
                                {antrean.map((r) => (
                                    <KartuKurasi key={r.id} resep={r} mode="antrean" />
                                ))}
                            </div>
                        )}
                    </section>

                    {/* ───────── Perlu diperiksa ───────── */}
                    <section>
                        <div className="flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <h2 className="font-mono text-xs uppercase tracking-wider text-muted">
                                    Pengawasan
                                </h2>
                                <p className="mt-1 font-display text-2xl font-bold tracking-tight text-charcoal">
                                    Disunting setelah terbit
                                </p>
                                <p className="mt-1 max-w-2xl font-body text-sm leading-relaxed text-muted">
                                    Resep ini sudah tayang lalu isinya diubah. Suntingan
                                    berlaku langsung — daftar ini tidak menahan apa pun,
                                    hanya memastikan tidak ada perubahan yang lolos tanpa
                                    dilihat.
                                </p>
                            </div>
                        </div>

                        {disunting.length === 0 ? (
                            <p className="mt-5 rounded-2xl border border-dashed border-border bg-surface px-6 py-10 text-center font-body text-sm text-muted">
                                Belum ada resep tayang yang disunting.
                            </p>
                        ) : (
                            <div className="mt-5 grid gap-5 lg:grid-cols-2">
                                {disunting.map((r) => (
                                    <KartuKurasi key={r.id} resep={r} mode="disunting" />
                                ))}
                            </div>
                        )}
                    </section>

                    <p className="flex items-center gap-2 border-t border-border pt-6 font-body text-xs text-muted">
                        <IconPencil className="h-3.5 w-3.5" />
                        Kurasi hanya menyetujui atau menolak — isi resep tetap milik
                        penulisnya.
                    </p>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
