import DangerButton from '@/Components/DangerButton';
import { IconBookmark, IconClock, IconHeart, IconPencil, IconPhoto, IconTrash } from '@/Components/Icons';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import StatusBadge from '@/Components/StatusBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { urlGambar } from '@/utils/gambar';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

const STATISTIK = [
    { kunci: 'total', label: 'Total resep' },
    { kunci: 'terbit', label: 'Sedang tayang' },
    { kunci: 'draft', label: 'Masih draft' },
    { kunci: 'menunggu', label: 'Menunggu ditinjau' },
];

const STATISTIK_KINERJA = [
    { kunci: 'suka', label: 'Suka diterima' },
    { kunci: 'simpanan', label: 'Simpanan diterima' },
    { kunci: 'unggulan', label: 'Resep unggulan' },
];

const bulat = (nilai) => Math.round(Number(nilai) || 0);

/**
 * Sub-tab: Studio adalah RUANG KERJA, bukan etalase.
 *
 * Dua kelompok status yang menuntut tindakan berbeda:
 *
 *  - Perlu Tindakan : draft (penulis mengajukan) + diajukan (Pengelola
 *                     meninjau) — inti antrean kerja studio
 *  - Tayang         : terbit + terverifikasi — hasil akhirnya; isi yang
 *                     sama persis dengan yang terlihat di Profil, jadi
 *                     dipisahkan supaya tidak terbaca sebagai duplikat
 *
 * Resep tidak pernah hilang: setiap status selalu masuk salah satu tab,
 * dan jumlahnya tertera di label.
 */
const KELOMPOK = {
    antrean: (r) => r.status === 'draft' || r.status === 'diajukan',
    tayang: (r) => r.status === 'terbit' || r.status === 'terverifikasi',
};

const TAB = [
    { kunci: 'antrean', label: 'Perlu Tindakan' },
    { kunci: 'tayang', label: 'Tayang' },
];

/** Opsi dropdown urutan daftar resep Studio. */
const OPSI_URUT = [
    { value: 'terbaru', label: 'Terbaru' },
    { value: 'disukai', label: 'Paling banyak disukai' },
    { value: 'disimpan', label: 'Paling banyak disimpan' },
];

/**
 * Urutkan daftar resep Studio sesuai pilihan dropdown.
 *
 * Dilakukan di klien karena `jumlah_suka` & `jumlah_simpanan` sudah ikut
 * dimuat lewat withCount pada controller — tanpa query tambahan.
 * `terbaru` mengembalikan urutan asli dari server (latest()).
 */
const urutkanStudio = (daftar, kunci) => {
    if (kunci === 'terbaru') {
        return daftar;
    }

    const salinan = [...daftar];

    if (kunci === 'disukai') {
        salinan.sort((a, b) => (Number(b.jumlah_suka) || 0) - (Number(a.jumlah_suka) || 0));
    } else if (kunci === 'disimpan') {
        salinan.sort((a, b) => (Number(b.jumlah_simpanan) || 0) - (Number(a.jumlah_simpanan) || 0));
    }

    return salinan;
};

export default function StudioIndex({ resep, kategoriTersedia, statistik }) {
    const { auth } = usePage().props;
    const [dihapus, setDihapus] = useState(null);
    const [sibuk, setSibuk] = useState(false);

    // Urutan daftar resep — diurutkan di klien karena jumlah suka & simpanan
    // sudah ikut dimuat lewat withCount, jadi tidak butuh permintaan server.
    const [urut, setUrut] = useState('terbaru');

    // Tab awal = yang PERTINGGI berisi: profil dengan semua resep tayang
    // tidak membuka pada daftar kosong, begitu pula sebaliknya.
    const [tab, setTab] = useState(
        resep.some(KELOMPOK.antrean) ? 'antrean' : 'tayang',
    );

    const tampilan = urutkanStudio(resep.filter(KELOMPOK[tab]), urut);
    const jumlahTab = {
        antrean: resep.filter(KELOMPOK.antrean).length,
        tayang: resep.filter(KELOMPOK.tayang).length,
    };

    const labelKategori = Object.fromEntries(
        kategoriTersedia.map((k) => [k.value, k.label]),
    );

    const bukaForm = () => router.visit(route('studio.create'));

    const konfirmasiHapus = () => {
        setSibuk(true);

        router.delete(route('studio.destroy', dihapus.id), {
            preserveScroll: true,
            onFinish: () => {
                setSibuk(false);
                setDihapus(null);
            },
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-bold leading-tight tracking-tight text-charcoal">
                            Studio Dapur
                        </h2>
                        <p className="mt-1 font-body text-sm text-muted">
                            Ruang kerja penyusunan resep.
                        </p>
                    </div>

                    <PrimaryButton type="button" onClick={bukaForm}>
                        Buat Resep
                    </PrimaryButton>
                </div>
            }
        >
            <Head title="Studio Dapur" />

            <div className="py-12">
                <div className="mx-auto max-w-shell space-y-6 sm:px-6 lg:px-8">
                    {/* Ringkasan singkat */}
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {STATISTIK.map((s) => (
                            <div
                                key={s.kunci}
                                className="rounded-xl border border-border bg-surface p-4 shadow-card"
                            >
                                <div className="font-mono text-3xl font-bold tabular-nums text-primary">
                                    {statistik?.[s.kunci] ?? 0}
                                </div>
                                <div className="mt-1 font-body text-xs uppercase tracking-wider text-muted">
                                    {s.label}
                                </div>
                            </div>
                        ))}
                    </div>

                    {/*
                        Kinerja di mata pembaca — hanya relevan bila memang ada
                        resep tayang. Tanpa syarat ini, akun yang semua resepnya
                        masih draft akan memamerkan tiga kartu "0" yang tidak
                        mengatakan apa-apa.
                    */}
                    {(statistik?.terbit ?? 0) > 0 && (
                        <div>
                            <h3 className="font-display text-sm font-bold uppercase tracking-wider text-muted">
                                Kinerja resep tayang
                            </h3>
                            <div className="mt-3 grid gap-4 sm:grid-cols-3">
                                {STATISTIK_KINERJA.map((s) => (
                                    <div
                                        key={s.kunci}
                                        className="rounded-xl border border-border bg-surface p-4 shadow-card"
                                    >
                                        <div className="font-mono text-3xl font-bold tabular-nums text-charcoal">
                                            {statistik?.[s.kunci] ?? 0}
                                        </div>
                                        <div className="mt-1 font-body text-xs uppercase tracking-wider text-muted">
                                            {s.label}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    <div className="border border-border bg-surface shadow-card sm:rounded-2xl">
                        <div className="p-6">
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div className="min-w-0">
                                    <h3 className="text-lg font-bold text-charcoal">
                                        Resep Anda
                                    </h3>
                                    <p className="mt-1 font-body text-sm leading-relaxed text-muted">
                                        {auth.user.role === 'pengelola'
                                            ? 'Sebagai Pengelola, Anda melihat seluruh resep termasuk yang dikirim Kreator.'
                                            : 'Susun, sunting, dan terbitkan resep milik Anda sendiri.'}
                                    </p>
                                </div>

                                {/* Urutkan daftar: terbaru / paling disukai / disimpan */}
                                {resep.length > 1 && (
                                    <label className="inline-flex shrink-0 items-center gap-2">
                                        <span className="font-body text-xs uppercase tracking-wider text-muted">
                                            Urutkan
                                        </span>
                                        <select
                                            value={urut}
                                            onChange={(e) => setUrut(e.target.value)}
                                            className="rounded-lg border border-border bg-surface px-3 py-2 font-body text-sm text-charcoal focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                        >
                                            {OPSI_URUT.map((o) => (
                                                <option key={o.value} value={o.value}>
                                                    {o.label}
                                                </option>
                                            ))}
                                        </select>
                                    </label>
                                )}
                            </div>

                            {/* ── Sub-tab antrean vs tayang ── */}
                            {resep.length > 0 && (
                            <div className="mt-5 flex gap-1 border-b border-border">
                                {TAB.map((t) => (
                                    <button
                                        key={t.kunci}
                                        type="button"
                                        onClick={() => setTab(t.kunci)}
                                        aria-current={tab === t.kunci ? 'page' : undefined}
                                        className={`-mb-px shrink-0 border-b-2 px-3 py-2 text-sm font-medium transition ${
                                            tab === t.kunci
                                                ? 'border-primary text-primary'
                                                : 'border-transparent text-muted hover:text-muted-dark'
                                        }`}
                                    >
                                        {t.label}{' '}
                                        <span className="font-mono text-xs tabular-nums">
                                            ({jumlahTab[t.kunci]})
                                        </span>
                                    </button>
                                ))}
                            </div>
                            )}

                            <div className="mt-6">
                                {tampilan.length === 0 ? (
                                    <div className="rounded-2xl border border-dashed border-border bg-warm-cream px-6 py-14 text-center">
                                        <IconPhoto className="mx-auto h-10 w-10 text-muted" />
                                        <h4 className="mt-4 text-base font-bold text-charcoal">
                                            {resep.length === 0
                                                ? 'Belum ada resep'
                                                : tab === 'antrean'
                                                  ? 'Tidak ada yang menunggu tindakan'
                                                  : 'Belum ada resep tayang'}
                                        </h4>
                                        <p className="mx-auto mt-1 max-w-md font-body text-sm leading-relaxed text-muted">
                                            {resep.length === 0
                                                ? 'Mulai dari resep pertama Anda — isi identitas, susun bahan dan langkah, lalu tinjau kartunya sebelum diterbitkan.'
                                                : tab === 'antrean'
                                                  ? 'Semua resep Anda sudah diajukan atau tayang. Buat draft baru bila ada ide lain.'
                                                  : 'Resep yang sudah lolos tinjauan Pengelola akan muncul di sini.'}
                                        </p>
                                        {resep.length === 0 && (
                                            <div className="mt-5">
                                                <PrimaryButton
                                                    type="button"
                                                    onClick={bukaForm}
                                                >
                                                    Buat Resep Pertama
                                                </PrimaryButton>
                                            </div>
                                        )}
                                    </div>
                                ) : (
                                    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                        {tampilan.map((r) => (
                                            <article
                                                key={r.id}
                                                className="group flex h-full flex-col overflow-hidden rounded-2xl border border-border bg-surface shadow-card transition duration-300 hover:-translate-y-1 hover:shadow-raised"
                                            >
                                                <div className="relative aspect-[4/3] overflow-hidden bg-linen">
                                                    {r.gambar ? (
                                                        <img
                                                            src={urlGambar(r.gambar)}
                                                            alt={r.judul}
                                                            loading="lazy"
                                                            className="h-full w-full object-cover"
                                                        />
                                                    ) : (
                                                        <div className="flex h-full w-full items-center justify-center text-muted">
                                                            <IconPhoto className="h-10 w-10" />
                                                        </div>
                                                    )}

                                                    {/* Status resep menempel di atas foto */}
                                                    <span className="absolute left-3 top-3">
                                                        <StatusBadge status={r.status} />
                                                    </span>
                                                </div>

                                                <div className="flex flex-1 flex-col p-5">
                                                    <h3 className="text-base font-bold leading-snug text-charcoal">
                                                        {r.judul}
                                                    </h3>

                                                    <p className="mt-1.5 line-clamp-2 font-body text-sm leading-relaxed text-muted">
                                                        {r.deskripsi}
                                                    </p>

                                                    {/*
                                                        Reputasi resep di mata
                                                        pembaca — "berapa yang
                                                        suka" dan "berapa yang
                                                        nyimpen". Angka 0 tetap
                                                        tampil: resep draft
                                                        memang belum ada yang
                                                        suka, dan itu informasi,
                                                        bukan kekosongan.
                                                    */}
                                                    <div className="mt-3 flex items-center gap-4 font-mono text-xs">
                                                        <span
                                                            className="inline-flex items-center gap-1.5"
                                                            title="Jumlah orang yang menyukai resep ini"
                                                        >
                                                            <IconHeart isi className="h-4 w-4 text-terracotta" />
                                                            <span className="font-bold tabular-nums text-charcoal">
                                                                {bulat(r.jumlah_suka)}
                                                            </span>
                                                            <span className="text-muted">suka</span>
                                                        </span>

                                                        <span
                                                            className="inline-flex items-center gap-1.5"
                                                            title="Jumlah orang yang menyimpan resep ini"
                                                        >
                                                            <IconBookmark className="h-4 w-4 text-primary" />
                                                            <span className="font-bold tabular-nums text-charcoal">
                                                                {bulat(r.jumlah_simpanan)}
                                                            </span>
                                                            <span className="text-muted">simpan</span>
                                                        </span>
                                                    </div>

                                                    <div className="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 font-mono text-[11px] text-muted">
                                                        <span>
                                                            {labelKategori[r.kategori] ??
                                                                r.kategori}
                                                        </span>
                                                        <span className="inline-flex items-center gap-1">
                                                            <IconClock className="h-3.5 w-3.5" />
                                                            {r.waktu_masak_menit} mnt
                                                        </span>
                                                        <span>{r.porsi} porsi</span>
                                                        <span>
                                                            {bulat(r.energi_kkal)} kkal
                                                        </span>
                                                        <span className="text-sage-dark">
                                                            P {bulat(r.protein_g)}g
                                                        </span>
                                                    </div>

                                                    {auth.user.role === 'pengelola' &&
                                                        r.penulis && (
                                                            <p className="mt-2 font-mono text-[11px] text-primary">
                                                                oleh{' '}
                                                                <Link
                                                                    href={route(
                                                                        'profil.show',
                                                                        r.penulis.username,
                                                                    )}
                                                                    className="underline-offset-4 hover:underline"
                                                                >
                                                                    {r.penulis.name}
                                                                </Link>
                                                            </p>
                                                        )}

                                                    {/* Aksi menempel ke dasar kartu */}
                                                    <div className="mt-auto flex flex-wrap gap-2 pt-4">
                                                    {r.status === 'draft' && (
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                router.post(
                                                                    route(
                                                                        'studio.ajukan-resep',
                                                                        r.id,
                                                                    ),
                                                                    {},
                                                                    { preserveScroll: true },
                                                                )
                                                            }
                                                            className="inline-flex min-h-9 items-center rounded-lg bg-primary px-4 py-2 font-display text-xs font-semibold text-white shadow-sm transition hover:bg-primary-dark"
                                                        >
                                                            Ajukan
                                                        </button>
                                                    )}

                                                    {r.status === 'diajukan' && (
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                router.post(
                                                                    route(
                                                                        'studio.tarik-resep',
                                                                        r.id,
                                                                    ),
                                                                    {},
                                                                    { preserveScroll: true },
                                                                )
                                                            }
                                                            className="inline-flex min-h-9 items-center rounded-lg border border-border px-4 py-2 font-display text-xs font-semibold text-charcoal transition hover:border-primary hover:text-primary"
                                                        >
                                                            Tarik pengajuan
                                                        </button>
                                                    )}

                                                    <SecondaryButton
                                                        type="button"
                                                        onClick={() =>
                                                            router.visit(
                                                                route(
                                                                    'studio.edit',
                                                                    r.id,
                                                                ),
                                                            )
                                                        }
                                                    >
                                                        <span className="inline-flex items-center gap-1.5">
                                                            <IconPencil className="h-4 w-4" />
                                                            Ubah
                                                        </span>
                                                    </SecondaryButton>

                                                    <button
                                                        type="button"
                                                        onClick={() => setDihapus(r)}
                                                        className="inline-flex items-center gap-1.5 rounded-lg border border-danger/30 bg-surface px-4 py-2 text-xs font-semibold uppercase tracking-widest text-danger shadow-sm transition hover:bg-danger/10 focus:outline-none focus:ring-2 focus:ring-danger focus:ring-offset-2 focus:ring-offset-warm-cream"
                                                    >
                                                        <IconTrash className="h-4 w-4" />
                                                        Hapus
                                                    </button>
                                                    </div>
                                                </div>
                                            </article>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Konfirmasi hapus */}
            <Modal
                show={Boolean(dihapus)}
                onClose={() => !sibuk && setDihapus(null)}
                maxWidth="md"
            >
                <div className="p-6">
                    <h3 className="text-lg font-bold text-charcoal">
                        Hapus resep ini?
                    </h3>
                    <p className="mt-2 font-body text-sm leading-relaxed text-muted">
                        <span className="font-semibold text-charcoal">
                            {dihapus?.judul}
                        </span>{' '}
                        beserta gambar lampirannya akan dihapus permanen. Tindakan
                        ini tidak bisa dibatalkan.
                    </p>

                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton
                            type="button"
                            disabled={sibuk}
                            onClick={() => setDihapus(null)}
                        >
                            Batal
                        </SecondaryButton>
                        <DangerButton
                            type="button"
                            disabled={sibuk}
                            onClick={konfirmasiHapus}
                        >
                            {sibuk ? 'Menghapus…' : 'Ya, Hapus'}
                        </DangerButton>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
