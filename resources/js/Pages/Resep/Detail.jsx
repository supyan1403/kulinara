import FotoResep from '@/Components/FotoResep';
import AvatarInisial from '@/Components/AvatarInisial';
import BagianUlasan from '@/Components/BagianUlasan';
import RecipeCard from '@/Components/RecipeCard';
import SiteFooter from '@/Components/SiteFooter';
import SiteHeader from '@/Components/SiteHeader';
import { IconArrowLeft, IconBookmark, IconCheck, IconClock, IconHeart } from '@/Components/Icons';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const tanggalPanjang = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

/**
 * Kunci penyimpanan tanda centang bahan, per resep.
 *
 * Disimpan di localStorage supaya memuat ulang halaman di tengah memasak tidak
 * menghapus tanda centang. Sifatnya murni nyaman — aplikasi tetap berfungsi
 * penuh bila penyimpanan ditolak (mode privat, kuota penuh).
 */
const kunciCentang = (slug) => `kulinara:centang:${slug}`;

function muatCentang(slug) {
    try {
        return new Set(JSON.parse(window.localStorage.getItem(kunciCentang(slug)) ?? '[]'));
    } catch {
        return new Set();
    }
}

/**
 * Halaman detail satu resep.
 *
 * Susunannya mengikuti urutan yang dipakai orang saat benar-benar memasak:
 *
 *   1. Kepala — foto besar di kiri, identitas resep dan kartu gizi di kanan.
 *   2. Bodi — daftar bahan yang bisa dicentang di rail kiri, langkah di kanan.
 *      Bahan sengaja dibuat sticky: saat mengikuti langkah ke tujuh, daftar
 *      bahan harusnya masih terbaca di layar.
 *   3. Kaki — resep lain sekategori, supaya halaman tidak berhenti mendadak.
 */
export default function Detail({
    auth,
    canLogin,
    canRegister,
    resep,
    kategoriLabel = '',
    kesulitanLabel = '',
    kategoriTersedia = [],
    terkait = [],
    disimpan = false,
    sudahSukaResep = false,
    sudahSukaProfil = false,
    ulasan = [],
    ulasanSaya = null,
    bolehBalas = false,
    standar = null,
}) {
    const bahan = Array.isArray(resep.bahan) ? resep.bahan : [];
    const langkah = Array.isArray(resep.langkah) ? resep.langkah : [];
    // `daerah` sengaja tidak ditampilkan, sama seperti di kartu resep:
    // hanya 47% resep terbit yang mengisinya.
    const tags = [kategoriLabel].filter(Boolean);

    const labelKategori = Object.fromEntries(kategoriTersedia.map((k) => [k.value, k.label]));

    const [tersedia, setTersedia] = useState(() => muatCentang(resep.slug));

    // ── Kalkulator skala produksi ──
    // Nilai awal = porsi asli resep (faktor 1). Penggeser mengubah porsi
    // target; semua bahan ikut terkonversi secara proporsional di klien.
    const porsiAsli = Math.max(1, Number(resep.porsi) || 1);
    const [porsiTarget, setPorsiTarget] = useState(porsiAsli);
    const faktorSkala = porsiTarget / porsiAsli;

    // Inertia memakai komponen yang sama untuk /resep/a -> /resep/b, jadi
    // state tidak ikut ter-reset sendiri. Muat ulang di sini, bukan sekadar
    // di useState().
    useEffect(() => {
        setTersedia(muatCentang(resep.slug));
        // Skala juga di-reset — porsi asli tiap resep berbeda.
        setPorsiTarget(Math.max(1, Number(resep.porsi) || 1));
    }, [resep.slug, resep.porsi]);

    useEffect(() => {
        try {
            window.localStorage.setItem(
                kunciCentang(resep.slug),
                JSON.stringify([...tersedia]),
            );
        } catch {
            // Penyimpanan ditolak: tanda centang tetap hidup di memori,
            // hanya kelangsungan lintas-sesi yang hilang.
        }
    }, [tersedia, resep.slug]);

    const semuaTersedia = bahan.length > 0 && tersedia.size === bahan.length;

    const toggle = (indeks) =>
        setTersedia((lama) => {
            const baru = new Set(lama);
            if (baru.has(indeks)) {
                baru.delete(indeks);
            } else {
                baru.add(indeks);
            }
            return baru;
        });

    const toggleSemua = () =>
        setTersedia(semuaTersedia ? new Set() : new Set(bahan.map((_, i) => i)));

    /**
     * Kunci tombol selama permintaan berjalan.
     *
     * `router.post` Inertia tidak membawa state `processing` bawaan seperti
     * `useForm`, dan tanpa pengunci ini klik ketiga dalam rentang milidetik
     * akan mengirim permintaan yang keadaannya sudah basi.
     */
    const [mengirim, setMengirim] = useState(false);

    const toggleSimpan = () => {
        if (mengirim) return;

        setMengirim(true);
        router.post(
            route('simpanan.toggle', resep.id),
            {},
            { preserveScroll: true, onFinish: () => setMengirim(false) },
        );
    };

    // ── Suka (like) ──
    // Satu rute POST per target; server membalik sendiri (ada → hapus,
    // tidak ada → buat). Kunci `mengirim` dipakai bersama toggleSimpan
    // supaya dua tombol tidak menembak permintaan berbarengan.
    const sukaResep = () => {
        if (mengirim) return;

        setMengirim(true);
        router.post(
            route('resep.suka', resep.slug),
            {},
            { preserveScroll: true, onFinish: () => setMengirim(false) },
        );
    };

    const sukaProfilPenulis = () => {
        if (mengirim || !resep.penulis?.username) return;

        setMengirim(true);
        router.post(
            route('profil.suka', resep.penulis.username),
            {},
            { preserveScroll: true, onFinish: () => setMengirim(false) },
        );
    };

    const gizi = [
        { label: 'Energi', nilai: Math.round(Number(resep.energi_kkal)), satuan: 'kkal' },
        { label: 'Protein', nilai: Number(resep.protein_g).toFixed(1), satuan: 'g' },
        { label: 'Lemak', nilai: Number(resep.lemak_g).toFixed(1), satuan: 'g' },
        { label: 'Karbohidrat', nilai: Number(resep.karbohidrat_g).toFixed(1), satuan: 'g' },
    ];

    return (
        <>
            <Head title={resep.judul} />

            <div className="min-h-screen bg-warm-cream text-charcoal">
                <SiteHeader
                    user={auth?.user}
                    canLogin={canLogin}
                    canRegister={canRegister}
                    beranda={false}
                />

                <main className="mx-auto max-w-shell px-4 py-10 sm:px-6 lg:px-8">
                    <Link
                        href={route('resep.index')}
                        className="inline-flex items-center gap-2 rounded-lg border border-border bg-surface px-3 py-1.5 font-body text-sm font-semibold text-charcoal shadow-sm transition hover:border-primary/30 hover:text-primary"
                    >
                        <IconArrowLeft className="h-4 w-4" />
                        Kembali ke semua resep
                    </Link>

                    {/* ───────── Kepala: foto + identitas ───────── */}
                    <div className="mt-6 grid gap-8 lg:grid-cols-2 lg:items-center lg:gap-12">
                        {/* Ring border bukan hiasan: foto berlatar putih seperti
                            cireng nyaris menyatu dengan warm-cream kalau
                            batasnya tidak digambar sendiri. Rasio 4:3 juga
                            rasio yang sama dipakai kartu resep. */}
                        <div className="relative aspect-[4/3] overflow-hidden rounded-2xl bg-linen shadow-card ring-1 ring-inset ring-border">
                            <FotoResep recipe={resep} eager />
                        </div>

                        <div>
                            <div className="flex flex-wrap items-center gap-2">
                                {tags.map((tag) => (
                                    <span
                                        key={tag}
                                        className="rounded-full bg-linen px-2.5 py-1 text-[11px] font-semibold text-charcoal ring-1 ring-inset ring-border"
                                    >
                                        {tag}
                                    </span>
                                ))}
                                {kesulitanLabel && (
                                    <span className="rounded-full bg-mint-light px-2.5 py-1 text-[11px] font-semibold text-primary ring-1 ring-inset ring-primary/15">
                                        Kesulitan {kesulitanLabel}
                                    </span>
                                )}
                                <span className="inline-flex items-center gap-1.5 rounded-full bg-surface px-2.5 py-1 text-[11px] font-semibold text-charcoal ring-1 ring-inset ring-border">
                                    <IconClock className="h-3.5 w-3.5 text-primary" />
                                    {resep.waktu_masak_menit} menit
                                </span>
                                {standar?.terpenuhi && (
                                    <span
                                        title="Gizi dihitung otomatis dari bahan, cakupan 100%, dan hasil akhir tercatat — resep ini bisa direproduksi."
                                        className="inline-flex items-center gap-1 rounded-full bg-sage-dark px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-white ring-1 ring-inset ring-sage-dark/20"
                                    >
                                        <IconCheck className="h-3 w-3" />
                                        Standar
                                    </span>
                                )}
                            </div>

                            <h1 className="mt-4 text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">
                                {resep.judul}
                            </h1>

                            {resep.deskripsi && (
                                <p className="mt-4 font-body text-base leading-relaxed text-muted">
                                    {resep.deskripsi}
                                </p>
                            )}

                            <div className="mt-4 flex flex-wrap items-center gap-3">
                                <div className="flex min-w-0 flex-1 items-center gap-3">
                                    <AvatarInisial
                                        nama={resep.penulis?.name ?? '?'}
                                        foto={resep.penulis?.avatar_path ?? null}
                                        ukuran="md"
                                    />
                                    <div className="min-w-0">
                                        <p className="truncate font-body text-sm font-semibold text-primary">
                                            {resep.penulis?.username ? (
                                                <Link
                                                    href={route('profil.show', resep.penulis.username)}
                                                    className="underline-offset-4 hover:underline"
                                                >
                                                    {resep.penulis.name}
                                                </Link>
                                            ) : (
                                                <span>{resep.penulis?.name ?? 'Penulis Kulinara'}</span>
                                            )}
                                        </p>
                                        <p className="font-body text-xs text-muted">
                                            {resep.created_at
                                                ? `Resep autentik · ${tanggalPanjang.format(new Date(resep.created_at))}`
                                                : 'Resep autentik Kulinara'}
                                        </p>
                                    </div>
                                </div>

                                {/*
                                    Suka profil — hanya untuk pengunjung lain:
                                    penulis sendiri tidak bisa menyukai
                                    profilmnya, dan tamu diarahkan ke halaman
                                    masuk (tombol tetap tampil supaya ia tahu
                                    fiturnya ada).
                                */}
                                {!bolehBalas && (
                                    auth?.user ? (
                                        <button
                                            type="button"
                                            onClick={sukaProfilPenulis}
                                            disabled={mengirim}
                                            aria-pressed={sudahSukaProfil}
                                            aria-label={
                                                sudahSukaProfil
                                                    ? 'Batalkan suka pada profil penulis'
                                                    : 'Suka profil penulis'
                                            }
                                            className={`inline-flex shrink-0 items-center gap-2 rounded-lg border px-3 py-2 text-sm font-semibold transition disabled:opacity-60 ${
                                                sudahSukaProfil
                                                    ? 'border-terracotta bg-terracotta/10 text-terracotta'
                                                    : 'border-border bg-surface text-charcoal hover:border-terracotta hover:text-terracotta'
                                            }`}
                                        >
                                            <IconHeart isi={sudahSukaProfil} className="h-4 w-4" />
                                            {sudahSukaProfil ? 'Disukai' : 'Suka Profil'}
                                        </button>
                                    ) : (
                                        <Link
                                            href={route('login')}
                                            className="inline-flex shrink-0 items-center gap-2 rounded-lg border border-border bg-surface px-3 py-2 text-sm font-semibold text-charcoal transition hover:border-terracotta hover:text-terracotta"
                                        >
                                            <IconHeart className="h-4 w-4" />
                                            Suka Profil
                                        </Link>
                                    )
                                )}
                            </div>

                            {/* ───────── Simpan + Suka ───────── */}
                            {/* Tombol Simpan SELALU tampil, termasuk untuk tamu.
                                Menyembunyikannya dari pengunjung yang belum
                                login justru menghapus satu-satunya alasan
                                baginya untuk mendaftar: ia sudah membaca resep
                                lengkap di halaman ini, dan kini ia kehilangan
                                sesuatu yang ingin disimpan. Login menjadi
                                alat, bukan pajak. */}
                            <div className="mt-5 flex flex-wrap items-center gap-3">
                                {auth?.user ? (
                                    <button
                                        type="button"
                                        onClick={toggleSimpan}
                                        disabled={mengirim}
                                        aria-pressed={disimpan}
                                        aria-label={
                                            disimpan ? 'Hapus dari simpanan' : 'Simpan resep'
                                        }
                                        className={`inline-flex items-center gap-2 rounded-lg border px-4 py-2.5 text-sm font-semibold transition disabled:opacity-60 ${
                                            disimpan
                                                ? 'border-primary bg-mint-light text-primary hover:bg-mint-light/60'
                                                : 'border-border bg-surface text-charcoal hover:border-primary hover:text-primary'
                                        }`}
                                    >
                                        <IconBookmark isi={disimpan} className="h-4 w-4" />
                                        {disimpan ? 'Tersimpan' : 'Simpan resep'}
                                    </button>
                                ) : (
                                    <Link
                                        href={route('login')}
                                        className="inline-flex items-center gap-2 rounded-lg border border-border bg-surface px-4 py-2.5 text-sm font-semibold text-charcoal transition hover:border-primary hover:text-primary"
                                    >
                                        <IconBookmark className="h-4 w-4" />
                                        Simpan resep
                                    </Link>
                                )}

                                {/*
                                    Suka resep — tombol like masakan. Penulis
                                    sendiri hanya melihat angkanya: like
                                    tidak bisa diberikan ke resep sendiri,
                                    jadi tombolnya memang tidak pernah ada
                                    untuk pemilik.
                                */}
                                {bolehBalas ? (
                                    Number(resep.jumlah_suka) > 0 && (
                                        <span
                                            className="inline-flex items-center gap-2 text-sm text-muted"
                                            title="Jumlah orang yang menyukai resep ini"
                                        >
                                            <IconHeart isi className="h-4 w-4 text-terracotta" />
                                            <span className="font-mono font-semibold tabular-nums">
                                                {Number(resep.jumlah_suka)}
                                            </span>
                                            disukai
                                        </span>
                                    )
                                ) : auth?.user ? (
                                    <button
                                        type="button"
                                        onClick={sukaResep}
                                        disabled={mengirim}
                                        aria-pressed={sudahSukaResep}
                                        aria-label={
                                            sudahSukaResep ? 'Batalkan suka' : 'Suka resep ini'
                                        }
                                        className={`inline-flex items-center gap-2 rounded-lg border px-4 py-2.5 text-sm font-semibold transition disabled:opacity-60 ${
                                            sudahSukaResep
                                                ? 'border-terracotta bg-terracotta/10 text-terracotta hover:bg-terracotta/20'
                                                : 'border-border bg-surface text-charcoal hover:border-terracotta hover:text-terracotta'
                                        }`}
                                    >
                                        <IconHeart isi={sudahSukaResep} className="h-4 w-4" />
                                        {sudahSukaResep ? 'Disukai' : 'Suka'}
                                        {Number(resep.jumlah_suka) > 0 && (
                                            <span className="font-mono tabular-nums">
                                                {Number(resep.jumlah_suka)}
                                            </span>
                                        )}
                                    </button>
                                ) : (
                                    <Link
                                        href={route('login')}
                                        className="inline-flex items-center gap-2 rounded-lg border border-border bg-surface px-4 py-2.5 text-sm font-semibold text-charcoal transition hover:border-terracotta hover:text-terracotta"
                                    >
                                        <IconHeart className="h-4 w-4" />
                                        Suka
                                        {Number(resep.jumlah_suka) > 0 && (
                                            <span className="font-mono tabular-nums">
                                                {Number(resep.jumlah_suka)}
                                            </span>
                                        )}
                                    </Link>
                                )}
                            </div>

                            {/* Save count statis di halaman detail — di bawah
                                baris tombol Simpan/Suka. Pemilik resep otomatis
                                menyimpan, jadi di sini juga hanya angka. */}
                            {Number(resep.jumlah_simpanan) > 0 && (
                                <p className="mt-2 flex items-center gap-1.5 text-sm text-muted">
                                    <IconBookmark className="h-4 w-4" />
                                    <span className="font-mono font-semibold tabular-nums">
                                        {Number(resep.jumlah_simpanan)}
                                    </span>
                                    disimpan
                                </p>
                            )}

                            {/* ───────── Kartu gizi ───────── */}
                            {/* Setiap pil kini menyebut namanya sendiri. Baris
                                "Energi" di daftar bawah sengaja dihapus — angka
                                kalori tidak boleh tampil dua kali. */}
                            <div className="mt-6 rounded-2xl border border-border bg-surface p-6 shadow-card">
                                <h2 className="font-bold">Nilai gizi per porsi</h2>

                                <div className="mt-4 grid grid-cols-2 gap-3">
                                    {gizi.map((item) => (
                                        <span
                                            key={item.label}
                                            className="flex flex-col rounded-xl bg-mint-light px-4 py-2.5 ring-1 ring-inset ring-primary/10"
                                        >
                                            <span className="font-mono text-[10px] font-semibold uppercase tracking-wider text-sage-dark">
                                                {item.label}
                                            </span>
                                            <span className="flex items-baseline gap-1">
                                                <span className="font-mono text-sm font-bold tabular-nums text-primary">
                                                    {item.nilai}
                                                </span>
                                                <span className="font-mono text-[11px] text-muted">
                                                    {item.satuan}
                                                </span>
                                            </span>
                                        </span>
                                    ))}
                                </div>

                                <dl className="mt-5 space-y-3 border-t border-border pt-4">
                                    <Rincian label="Jumlah porsi" isi={`${resep.porsi} porsi`} />
                                    <Rincian label="Waktu masak" isi={`${resep.waktu_masak_menit} menit`} />
                                    <Rincian label="Tingkat kesulitan" isi={kesulitanLabel || '-'} />
                                    {standar?.hasilTotal != null && (
                                        <Rincian
                                            label="Hasil akhir"
                                            isi={`${standar.hasilTotal} ${standar.satuanHasil === 'ml' ? 'ml' : 'g'}`}
                                        />
                                    )}
                                    {standar?.rendemen != null && (
                                        <Rincian label="Rendemen" isi={`${standar.rendemen}%`} />
                                    )}
                                </dl>

                                <p className="mt-5 font-body text-xs leading-relaxed text-muted">
                                    Angka gizi adalah perkiraan per porsi, bukan hasil
                                    pengukuran laboratorium.
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* ───────── Bodi: bahan + langkah ───────── */}
                    <div className="mt-14 grid gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,2fr)] lg:gap-14">
                        <section aria-labelledby="judul-bahan">
                            <div className="rounded-2xl border border-border bg-surface shadow-card lg:sticky lg:top-24">
                                <div className="flex items-baseline justify-between gap-3 border-b border-border px-6 py-4">
                                    <h2 id="judul-bahan" className="text-lg font-extrabold tracking-tight">
                                        Bahan
                                        <span className="ml-2 font-mono text-sm font-normal text-muted">
                                            {bahan.length} item
                                        </span>
                                    </h2>

                                    {bahan.length > 0 && (
                                        <button
                                            type="button"
                                            onClick={toggleSemua}
                                            className="shrink-0 font-body text-xs font-semibold text-primary transition hover:text-primary-dark"
                                        >
                                            {semuaTersedia ? 'Kosongkan' : 'Centang semua'}
                                        </button>
                                    )}
                                </div>

                                {/* ── Kalkulator skala produksi ── */}
                                {/* Hanya muncul bila resep memenuhi syarat
                                    Standar (gizi otomatis penuh + hasil
                                    tercatat) — tanpa itu, menskalakan bahan
                                    menimbulkan ilusi presisi yang tidak bisa
                                    dipertanggungjawabkan. */}
                                {standar?.terpenuhi && bahan.length > 0 && (
                                    <div className="border-b border-border bg-mint-light/40 px-6 py-4">
                                        <div className="flex items-baseline justify-between gap-3">
                                            <label
                                                htmlFor="skala-porsi"
                                                className="font-body text-sm font-semibold text-charcoal"
                                            >
                                                Skala produksi
                                            </label>
                                            <span className="font-mono text-sm font-bold tabular-nums text-primary">
                                                {porsiTarget} porsi
                                            </span>
                                        </div>

                                        <input
                                            id="skala-porsi"
                                            type="range"
                                            min={1}
                                            max={Math.max(10, porsiAsli * 10)}
                                            step={1}
                                            value={porsiTarget}
                                            onChange={(e) => setPorsiTarget(Number(e.target.value))}
                                            className="mt-3 w-full accent-primary"
                                            aria-valuenow={porsiTarget}
                                            aria-valuemin={1}
                                            aria-valuemax={Math.max(10, porsiAsli * 10)}
                                        />

                                        <div className="mt-2 flex items-center justify-between font-mono text-[11px] text-muted">
                                            <span>asli {porsiAsli} porsi</span>
                                            {faktorSkala !== 1 && (
                                                <button
                                                    type="button"
                                                    onClick={() => setPorsiTarget(porsiAsli)}
                                                    className="font-semibold text-primary transition hover:text-primary-dark"
                                                >
                                                    Reset ×{faktorSkala.toFixed(2)}
                                                </button>
                                            )}
                                        </div>

                                        {standar.hasilTotal != null && (
                                            <p className="mt-2 font-body text-xs text-sage-dark">
                                                Perkiraan hasil:{' '}
                                                <b className="font-mono">
                                                    {Math.round(standar.hasilTotal * faktorSkala * 100) / 100}{' '}
                                                    {standar.satuanHasil === 'ml' ? 'ml' : 'g'}
                                                </b>
                                            </p>
                                        )}
                                    </div>
                                )}

                                {bahan.length > 0 ? (
                                    <ul className="divide-y divide-border px-6">
                                        {bahan.map((item, i) => {
                                            const ditandai = tersedia.has(i);
                                            const jumlahAsli = Number(item.jumlah) || 0;
                                            const jumlahSkala =
                                                Math.round(jumlahAsli * faktorSkala * 100) / 100;

                                            return (
                                                <li key={`${item.nama}-${i}`}>
                                                    <label className="flex cursor-pointer items-start gap-3 py-3">
                                                        <input
                                                            type="checkbox"
                                                            checked={ditandai}
                                                            onChange={() => toggle(i)}
                                                            className="peer sr-only"
                                                        />
                                                        <span
                                                            aria-hidden="true"
                                                            className={`mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-md border transition peer-focus-visible:ring-2 peer-focus-visible:ring-primary peer-focus-visible:ring-offset-2 ${
                                                                ditandai
                                                                    ? 'border-primary bg-primary text-white'
                                                                    : 'border-border bg-linen text-transparent'
                                                            }`}
                                                        >
                                                            <IconCheck className="h-3.5 w-3.5" />
                                                        </span>

                                                        <span
                                                            className={`flex-1 font-body text-sm ${
                                                                ditandai ? 'text-muted line-through' : 'text-charcoal'
                                                            }`}
                                                        >
                                                            {item.nama}
                                                        </span>

                                                        <span
                                                            className={`shrink-0 font-mono text-sm tabular-nums ${
                                                                ditandai ? 'text-muted/60' : 'text-muted'
                                                            }`}
                                                        >
                                                            {faktorSkala !== 1 ? (
                                                                <>
                                                                    <span className="text-charcoal">{jumlahSkala}</span>{' '}
                                                                    {item.satuan}
                                                                    <span className="ml-1 text-[10px] text-muted/60">
                                                                        (asli {jumlahAsli})
                                                                    </span>
                                                                </>
                                                            ) : (
                                                                <>
                                                                    {item.jumlah} {item.satuan}
                                                                </>
                                                            )}
                                                        </span>
                                                    </label>
                                                </li>
                                            );
                                        })}
                                    </ul>
                                ) : (
                                    <p className="px-6 py-8 text-center font-body text-sm text-muted">
                                        Daftar bahan belum diisi oleh penulisnya.
                                    </p>
                                )}

                                {bahan.length > 0 && (
                                    <p
                                        aria-live="polite"
                                        className="border-t border-border px-6 py-3 font-mono text-xs text-muted"
                                    >
                                        {tersedia.size} dari {bahan.length} bahan sudah ditandai
                                    </p>
                                )}
                            </div>
                        </section>

                        <section aria-labelledby="judul-langkah">
                            <h2 id="judul-langkah" className="text-lg font-extrabold tracking-tight">
                                Langkah memasak
                            </h2>

                            {langkah.length > 0 ? (
                                <ol className="mt-5 space-y-5">
                                    {langkah.map((teks, i) => (
                                        <li key={i} className="flex gap-4">
                                            <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary font-mono text-xs font-bold text-white">
                                                {i + 1}
                                            </span>
                                            <p className="pt-0.5 font-body text-sm leading-relaxed text-charcoal">
                                                {teks}
                                            </p>
                                        </li>
                                    ))}
                                </ol>
                            ) : (
                                <p className="mt-5 rounded-2xl border border-dashed border-border bg-surface px-6 py-8 text-center font-body text-sm text-muted">
                                    Langkah memasak belum diisi oleh penulisnya.
                                </p>
                            )}
                        </section>
                    </div>

                    {/* ───────── Ulasan & rating ───────── */}
                    <BagianUlasan
                        resepId={resep.id}
                        ulasan={ulasan}
                        ulasanSaya={ulasanSaya}
                        bolehBalas={bolehBalas}
                        masuk={Boolean(auth?.user)}
                        rataRating={resep.rata_rating}
                        jumlahUlasan={resep.jumlah_ulasan}
                    />

                    {/* ───────── Kaki: resep lain ───────── */}
                    {terkait.length > 0 && (
                        <section className="mt-16 border-t border-border pt-10">
                            <h2 className="text-2xl font-extrabold tracking-tight">
                                Resep lain{kategoriLabel ? ` dari ${kategoriLabel}` : ''}
                            </h2>

                            <div className="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                                {terkait.map((recipe) => (
                                    <RecipeCard
                                        key={recipe.slug}
                                        recipe={recipe}
                                        labelKategori={labelKategori}
                                    />
                                ))}
                            </div>
                        </section>
                    )}
                </main>

                <SiteFooter canLogin={canLogin} canRegister={canRegister} beranda={false} />
            </div>
        </>
    );
}

function Rincian({ label, isi }) {
    return (
        <div className="flex items-baseline justify-between gap-4">
            <dt className="font-body text-sm text-muted">{label}</dt>
            <dd className="font-mono text-sm font-semibold tabular-nums text-charcoal">{isi}</dd>
        </div>
    );
}
