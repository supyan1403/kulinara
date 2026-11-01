import ChipKategori from '@/Components/ChipKategori';
import FormPencarian from '@/Components/FormPencarian';
import Reveal from '@/Components/Reveal';
import RecipeCard from '@/Components/RecipeCard';
import SiteFooter from '@/Components/SiteFooter';
import SiteHeader from '@/Components/SiteHeader';
import { Head, Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';

/* ───────────────────────── Ikon (SVG inline, tanpa font eksternal) ───────────────────────── */

const Svg = ({ children, className = 'h-5 w-5' }) => (
    <svg
        className={className}
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="1.5"
        strokeLinecap="round"
        strokeLinejoin="round"
        aria-hidden="true"
    >
        {children}
    </svg>
);

const IconChevron = (p) => (
    <Svg {...p}>
        <path d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
    </Svg>
);
const IconUsers = (p) => (
    <Svg {...p}>
        <path d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
    </Svg>
);
// Berkas ini dulu menamainya `IconChef`, padahal bentuknya jelas pensil.
// Nama itu salah, dan kebetulan ia justru cocok untuk peran yang kini
// disebut "Kreator" — orang yang menulis resep, bukan gelar profesi.
const IconPencil = (p) => (
    <Svg {...p}>
        <path d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
    </Svg>
);
const IconShield = (p) => (
    <Svg {...p}>
        <path d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
    </Svg>
);

/* ───────────────────────── Konten ───────────────────────── */

/**
 * Ekosistem Kulinara: dua peran pengguna, plus satu proses yang berjalan
 * di atas keduanya.
 *
 * Kartu ketiga sengaja ditandai "Proses", bukan "Peran". Dulu ia berbunyi
 * "Divalidasi Ahli Gizi" dan disajikan sejajar dengan dua peran lain, tapi
 * peran semacam itu tidak pernah ada di `UserRole` — yang benar-benar ada
 * hanya `StatusResep::Terverifikasi`, sebuah penanda pada RESEP, dan hanya
 * Pengelola yang boleh memasangnya (lihat StatusResep::diizinkanUntuk).
 * Menyebutnya peran berarti menjanjikan jabatan yang tidak bisa dipegang
 * siapa pun, dan yang juga tidak bisa ditunjuk kepada siapa pun.
 *
 * Deskripsi tiap kartu tetap ditulis hanya memuat hal yang benar-benar ada
 * di aplikasi hari ini — kartu "Penikmat Resep" dulu menjanjikan daftar
 * favorit dan penyesuaian porsi otomatis, padahal keduanya tidak pernah
 * dibangun.
 */
const EKOSISTEM = [
    {
        bidang: 'Langkah 01',
        name: 'Jelajahi & Masak',
        icon: IconUsers,
        iconTone: 'bg-linen text-charcoal',
        desc: 'Temukan ratusan resep autentik Nusantara lengkap dengan takaran bahan, langkah detail, dan estimasi gizinya tanpa syarat rumit.',
    },
    {
        bidang: 'Langkah 02',
        name: 'Tulis & Bagikan Resep',
        icon: IconPencil,
        iconTone: 'bg-terracotta/10 text-[#9c4526]',
        desc: 'Buat akun gratis dan gunakan Studio Dapur untuk mengabadikan resep andalan keluarga Anda dengan panduan takaran yang rapi.',
    },
    {
        bidang: 'Langkah 03',
        name: 'Kurasi & Terbit',
        icon: IconShield,
        iconTone: 'bg-mint-light text-primary',
        desc: 'Setiap resep yang diajukan ditinjau agar langkahnya jelas dan bahan-bahannya akurat sebelum hadir dinikmati seluruh komunitas.',
    },
];

const FAQ = [
    {
        q: 'Apakah saya harus mendaftar akun untuk melihat resep dan kalori?',
        a: 'Tidak sama sekali. Seluruh direktori resep Nusantara dan informasi nilai gizi dasarnya terbuka bebas untuk publik. Pembuatan akun hanya dibutuhkan jika Anda ingin menerbitkan resep Anda sendiri.',
    },
    {
        q: 'Seberapa akurat perkiraan gizi tiap hidangan?',
        a: 'Setiap resep menampilkan perkiraan kalori dan makronutrisi per porsinya. Angka itu perkiraan kasar, bukan hasil pengukuran laboratorium, sehingga cukup dipakai sebagai gambaran umum. Untuk kebutuhan medis atau diet tertentu, hitung bersama tenaga gizi — jangan mengandalkan angka di sini.',
    },
    {
        q: 'Siapa saja yang bisa mempublikasikan resep?',
        a: 'Tidak semua resep orang. Yang bisa dipublikasikan adalah resep milik Anda sendiri, dan itu tidak terjadi otomatis: Anda menulisnya di Studio Dapur, mengajukannya, lalu Pengelola meninjau sebelum tayang. Adapun penandaan Terverifikasi bukan sesuatu yang bisa diminta Kreator — hanya Pengelola yang boleh memasangnya.',
    },
];

/* ───────────────────────── Halaman ───────────────────────── */

export default function Welcome({
    auth,
    canLogin,
    canRegister,
    resep = [],
    unggulan = [],
    kategoriTersedia = [],
    kategoriAktif = null,
    kataCari = '',
    jumlahResep = 0,
    jumlahHasil = 0,
}) {
    const user = auth?.user;
    const [openFaq, setOpenFaq] = useState(0);

    /* Pemutar intro (opsi C): fullscreen, tutup dengan Esc/klik latar */
    const [intro, setIntro] = useState(false);
    useEffect(() => {
        if (!intro) return;
        const onKey = (e) => { if (e.key === 'Escape') setIntro(false); };
        addEventListener('keydown', onKey);
        return () => removeEventListener('keydown', onKey);
    }, [intro]);

    // Pemetaan value enum -> label Indonesia, agar kartu memakai sumber yang sama.
    const labelKategori = Object.fromEntries(kategoriTersedia.map((k) => [k.value, k.label]));

    const sedangMenyaring = Boolean(kataCari || kategoriAktif);

    // "Lihat Semua" membawa saringan yang sedang berjalan, supaya artinya
    // tetap konsisten: bila pengguna sedang mencari "ayam", yang dilihat
    // adalah semua hasil "ayam" di direktori — bukan kembali ke tanpa saringan.
    const tampilSemua = route('resep.index', {
        ...(kataCari ? { q: kataCari } : {}),
        ...(kategoriAktif ? { kategori: kategoriAktif } : {}),
    });

    return (
        <>
            <Head title="Masak Enak Nusantara, Kenali Gizinya" />

            <div className="relative min-h-screen bg-warm-cream text-charcoal">
                {/* Aksen pendar cahaya lembut di area hero */}
                <div className="pointer-events-none absolute inset-0 z-0 overflow-hidden">
                    <div className="absolute -left-24 -top-24 h-96 w-96 rounded-full bg-mint-light/40 blur-3xl" />
                    <div className="absolute -right-24 top-48 h-96 w-96 rounded-full bg-terracotta/10 blur-3xl" />
                </div>

                <SiteHeader
                    user={user}
                    canLogin={canLogin}
                    canRegister={canRegister}
                    beranda
                />

                {/* ───────── Hero ───────── */}
                <section className="relative z-10 flex min-h-screen items-center overflow-hidden bg-warm-cream">
                    {/* Latar belakang hero */}
                    <div
                        className="absolute inset-0 bg-cover bg-center"
                        style={{ backgroundImage: "url('/images/landing/hero-background.png')" }}
                        aria-hidden="true"
                    />
                    {/* Selimut krem supaya teks tetap terbaca di atas foto */}
                    <div className="absolute inset-0 bg-warm-cream/80" aria-hidden="true" />

                    <div className="relative mx-auto w-full max-w-shell px-4 pb-16 pt-24 sm:px-6 lg:px-8 lg:pb-24 lg:pt-28">
                        <div className="mx-auto max-w-3xl text-center">
                            <Reveal delay={80}>
                                <h1 className="text-4xl font-extrabold leading-[1.15] tracking-tight sm:text-5xl lg:text-6xl">
                                    Masak Enak Nusantara,
                                    <br />
                                    Kenali <span className="text-terracotta">Gizinya</span>.
                                </h1>
                            </Reveal>

                            <Reveal delay={160}>
                                <p className="mx-auto mt-6 max-w-2xl font-body text-base leading-relaxed text-muted sm:text-lg">
                                    Jelajahi resep autentik Indonesia, lengkap dengan perkiraan
                                    kalori dan makronutrisi per porsi. Dirawat bersama oleh
                                    komunitas kuliner Nusantara.
                                </p>
                            </Reveal>

                            <Reveal delay={240}>
                                <div className="mx-auto mt-8 max-w-xl">
                                    <FormPencarian tujuan="/" kataCari={kataCari} kategoriAktif={kategoriAktif} />
                                </div>
                            </Reveal>




                        </div>
                    </div>
                </section>

                {/* ───────── Direktori resep ───────── */}
                <div className="relative z-10 text-charcoal">
                    <section id="resep" className="mx-auto max-w-shell px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
                    <Reveal className="flex flex-wrap items-end justify-between gap-4">
                        <div className="max-w-2xl">
                            <span className="font-mono text-xs font-semibold uppercase tracking-wider text-primary">
                                Jelajah Resep
                            </span>
                            <h2 className="mt-2 text-3xl font-extrabold tracking-tight">
                                Resep dari Seluruh Penjuru Nusantara
                            </h2>
                        </div>

                        <div className="flex items-center gap-4">
                            {/* Angka jujur dari database, di samping pintu masuk
                                ke seluruh direktori. */}
                            <p className="font-mono text-xs text-muted" aria-live="polite">
                                {sedangMenyaring
                                    ? `${jumlahHasil} dari ${jumlahResep} resep`
                                    : `${jumlahResep} resep · ${kategoriTersedia.length} kategori`}
                            </p>

                            <Link
                                href={tampilSemua}
                                className="inline-flex items-center gap-1.5 rounded-full bg-surface px-4 py-2 text-sm font-semibold text-charcoal ring-1 ring-inset ring-border transition hover:bg-linen hover:text-primary"
                            >
                                Lihat Semua
                                <svg
                                    className="h-3.5 w-3.5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2.5"
                                    aria-hidden="true"
                                >
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </Link>
                        </div>
                    </Reveal>

                    {/* Kategori — datang dari enum di database */}
                    <Reveal delay={80}>
                        <ChipKategori
                            tujuan="/"
                            kategoriTersedia={kategoriTersedia}
                            kategoriAktif={kategoriAktif}
                            kataCari={kataCari}
                        />
                    </Reveal>

                    {resep.length > 0 ? (
                        <div className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                            {resep.map((recipe, i) => (
                                <Reveal key={recipe.slug} delay={80 + i * 70} className="h-full">
                                    <RecipeCard recipe={recipe} labelKategori={labelKategori} />
                                </Reveal>
                            ))}
                        </div>
                    ) : (
                        <Reveal delay={80}>
                            <p className="mt-8 rounded-2xl border border-dashed border-border bg-surface px-6 py-12 text-center font-body text-sm text-muted">
                                {kataCari || kategoriAktif
                                    ? `Tidak ada resep${kataCari ? ` untuk “${kataCari}”` : ''}${
                                          kategoriAktif
                                              ? ` pada kategori ${labelKategori[kategoriAktif] ?? kategoriAktif}`
                                              : ''
                                      }. Coba kata kunci atau kategori lain.`
                                    : 'Belum ada resep terbit saat ini.'}
                            </p>
                        </Reveal>
                    )}
                    </section>
                </div>

                {/* ───────── Ekosistem: alur resep ───────── */}
                <section id="peran" className="relative z-10 text-charcoal">
                    <div className="mx-auto max-w-shell px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
                        <Reveal className="max-w-2xl">
                            <span className="font-mono text-xs font-semibold uppercase tracking-wider text-primary">
                                Cara Kerja
                            </span>
                            <h2 className="mt-2 text-3xl font-extrabold tracking-tight">
                                Dari Dapur Rumah ke Meja Nusantara
                            </h2>
                            <p className="mt-3 font-body text-base leading-relaxed text-muted">
                                Semua orang bisa menemukan inspirasi masak lezat, dan siapa pun
                                bisa mengabadikan resep warisan keluarganya.
                            </p>
                        </Reveal>

                        <div className="mt-10 grid gap-6 sm:grid-cols-3">
                            {EKOSISTEM.map((entri, i) => {
                                const RoleIcon = entri.icon;
                                return (
                                    <Reveal key={entri.name} delay={i * 90} className="h-full">
                                        <div className="h-full rounded-2xl border border-border bg-surface p-6 shadow-card transition hover:-translate-y-0.5 hover:shadow-raised">
                                            <div
                                                className={`flex h-11 w-11 items-center justify-center rounded-xl ring-1 ring-inset ring-border ${entri.iconTone}`}
                                            >
                                                <RoleIcon className="h-5 w-5" />
                                            </div>

                                            {/* Penanda "Peran"/"Proses" ini beban
                                                penting: tanpanya kartu ketiga kembali
                                                terbaca sebagai jabatan, dan tiga kartu
                                                itu kembali terlihat setara. */}
                                            <p className="mt-4 font-mono text-[11px] font-semibold uppercase tracking-wider text-muted">
                                                {entri.bidang}
                                            </p>
                                            <h3 className="mt-1 font-bold">{entri.name}</h3>
                                            <p className="mt-2 font-body text-sm leading-relaxed text-muted">
                                                {entri.desc}
                                            </p>
                                        </div>
                                    </Reveal>
                                );
                            })}
                        </div>
                    </div>
                </section>

                {/* ───────── Intro motion (video promo 45 dtk) ───────── */}
                <section id="intro" className="relative z-10 text-charcoal">
                    <div className="mx-auto max-w-shell px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
                        <Reveal className="flex flex-wrap items-end justify-between gap-6">
                            <div className="max-w-2xl">
                                <span className="font-mono text-xs font-semibold uppercase tracking-wider text-primary">
                                    Intro
                                </span>
                                <h2 className="mt-2 text-3xl font-extrabold tracking-tight">
                                    Lihat Kulinara Bergerak
                                </h2>
                                <p className="mt-3 font-body text-base leading-relaxed text-muted">
                                    Empat puluh lima detik dari satu piring ke seluruh dapur —
                                    cari resep, simpan favoritmu, dan buka dapurmu sendiri.
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setIntro(true)}
                                className="inline-flex min-h-11 items-center gap-2 rounded-lg bg-primary px-6 py-3 text-sm font-semibold text-white shadow-raised transition hover:bg-primary-dark"
                            >
                                <svg className="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M8 5.5v13l11-6.5-11-6.5z" />
                                </svg>
                                Putar intro
                            </button>
                        </Reveal>

                        <Reveal delay={120}>
                            <button
                                type="button"
                                onClick={() => setIntro(true)}
                                aria-label="Putar video intro Kulinara"
                                className="group relative mt-8 block w-full overflow-hidden rounded-3xl border border-border bg-linen shadow-card transition hover:-translate-y-1 hover:shadow-raised focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                            >
                                <img
                                    src="/video/kulinara-promo.jpg"
                                    alt="Cuplikan video intro Kulinara"
                                    className="block w-full transition duration-500 group-hover:scale-[1.02]"
                                    loading="lazy"
                                />
                                <span className="absolute inset-0 flex items-center justify-center bg-charcoal/25 transition group-hover:bg-charcoal/35">
                                    <span className="flex h-20 w-20 items-center justify-center rounded-full bg-surface/95 shadow-raised">
                                        <svg className="ml-1 h-8 w-8 text-primary" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="M8 5.5v13l11-6.5-11-6.5z" />
                                        </svg>
                                    </span>
                                </span>
                                <span className="absolute bottom-4 left-4 rounded-full bg-surface/90 px-3 py-1 font-mono text-[11px] font-semibold uppercase tracking-wider text-muted ring-1 ring-inset ring-border">
                                    45 detik · bersuara
                                </span>
                            </button>
                        </Reveal>
                    </div>
                </section>

                {/* ───────── Pemutar video fullscreen ───────── */}
                {intro && (
                    <div
                        className="fixed inset-0 z-[60] flex items-center justify-center bg-black/95 p-4 sm:p-8"
                        onClick={() => setIntro(false)}
                        role="dialog"
                        aria-modal="true"
                        aria-label="Video intro Kulinara"
                    >
                        <video
                            src="/video/kulinara-promo.mp4"
                            controls
                            autoPlay
                            playsInline
                            className="w-full max-w-6xl rounded-2xl shadow-overlay"
                            onClick={(e) => e.stopPropagation()}
                        />
                        <button
                            type="button"
                            onClick={() => setIntro(false)}
                            aria-label="Tutup video"
                            className="absolute right-4 top-4 flex h-11 w-11 items-center justify-center rounded-full bg-surface/90 text-charcoal shadow-raised transition hover:bg-surface sm:right-6 sm:top-6"
                        >
                            <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" aria-hidden="true">
                                <path d="M6 6l12 12M18 6L6 18" />
                            </svg>
                        </button>
                    </div>
                )}

                {/* ───────── FAQ ───────── */}
                <section id="faq" className="relative z-10 text-charcoal">
                    <div className="mx-auto max-w-shell px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
                        <div className="grid gap-10 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
                            <Reveal>
                                <span className="font-mono text-xs font-semibold uppercase tracking-wider text-primary">
                                    Tanya Jawab
                                </span>
                                <h2 className="mt-2 text-3xl font-extrabold tracking-tight">
                                    Pertanyaan yang Sering Diajukan
                                </h2>
                                <p className="mt-3 font-body text-base leading-relaxed text-muted">
                                    Segala yang perlu Anda ketahui tentang cara Kulinara
                                    bekerja.
                                </p>
                            </Reveal>

                            <Reveal delay={100} className="divide-y divide-border border-y border-border">
                                {FAQ.map((item, i) => {
                                    const open = openFaq === i;
                                    return (
                                        <div key={item.q}>
                                            <button
                                                type="button"
                                                onClick={() => setOpenFaq(open ? -1 : i)}
                                                aria-expanded={open}
                                                className="flex w-full items-center justify-between gap-4 py-5 text-left"
                                            >
                                                <span
                                                    className={`font-semibold transition ${
                                                        open ? 'text-primary' : 'text-charcoal hover:text-primary'
                                                    }`}
                                                >
                                                    {item.q}
                                                </span>
                                                <IconChevron
                                                    className={`h-5 w-5 shrink-0 text-muted transition-transform duration-300 ${
                                                        open ? 'rotate-180 text-primary' : ''
                                                    }`}
                                                />
                                            </button>

                                            <div
                                                className={`grid transition-all duration-300 ease-out ${
                                                    open
                                                        ? 'grid-rows-[1fr] opacity-100'
                                                        : 'grid-rows-[0fr] opacity-0'
                                                }`}
                                            >
                                                <div className="overflow-hidden">
                                                    <p className="pb-5 font-body text-sm leading-relaxed text-muted">
                                                        {item.a}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </Reveal>
                        </div>
                    </div>
                </section>

                {/* ───────── CTA ───────── */}
                {!user && canRegister && (
                    <section className="mx-auto max-w-shell px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
                        <Reveal className="relative overflow-hidden rounded-2xl bg-primary px-6 py-14 text-center text-white shadow-raised sm:px-12">
                            <svg
                                className="pointer-events-none absolute -left-16 -bottom-16 h-64 w-64 text-mint-spark/10"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <path d="M12 2.5L13.2 4.5L15 5.5L13.2 6.5L12 8.5L10.8 6.5L9 5.5L10.8 4.5Z" />
                            </svg>

                            <h2 className="relative text-3xl font-extrabold tracking-tight">
                                Punya Resep Warisan yang Layak Dicatat?
                            </h2>
                            <p className="relative mx-auto mt-4 max-w-2xl font-body text-base leading-relaxed text-white/80">
                                Daftar gratis, tulis langkah demi langkah di Studio Dapur, dan
                                hadirkan resep Anda di antara {jumlahResep} hidangan Nusantara
                                lainnya.
                            </p>

                            {/* Putih dengan teks `primary`, menggantikan
                                terracotta-dark. Di atas pita hijau tua, aksen
                                oranye justru memperebutkan titik fokus dengan
                                warna band-nya sendiri. Putih memberi 9.85:1
                                terhadap #044d3c — jauh melampaui ambang 4.5:1
                                untuk teks text-sm — dan membuat pitanya
                                terasa lebih bersih. Hover turun ke mint-light
                                (8.98:1), masih dalam keluarga hijau. */}
                            <Link
                                href={route('register')}
                                className="relative mt-8 inline-block rounded-lg bg-white px-8 py-3 text-sm font-semibold text-primary shadow-raised transition hover:bg-mint-light"
                            >
                                Daftar Gratis Sekarang
                            </Link>
                        </Reveal>
                    </section>
                )}

                <SiteFooter canLogin={canLogin} canRegister={canRegister} beranda />
            </div>
        </>
    );
}
