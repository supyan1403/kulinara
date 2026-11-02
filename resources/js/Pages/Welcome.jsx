import ChipKategori from '@/Components/ChipKategori';
import FormPencarian from '@/Components/FormPencarian';
import Reveal from '@/Components/Reveal';
import RecipeCard from '@/Components/RecipeCard';
import SiteFooter from '@/Components/SiteFooter';
import SiteHeader from '@/Components/SiteHeader';
import { Head, Link } from '@inertiajs/react';
import { motion, useScroll, useTransform, AnimatePresence } from 'framer-motion';
import { useRef, useState } from 'react';

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
    const heroRef = useRef(null);

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

    // Parallax hero + scroll-driven effects
    const { scrollY } = useScroll();
    const heroY = useTransform(scrollY, [0, 500], [0, -80]);
    const heroOpacity = useTransform(scrollY, [0, 400], [1, 0]);
    const blobY1 = useTransform(scrollY, [0, 500], [0, 40]);
    const blobY2 = useTransform(scrollY, [0, 500], [0, -30]);
    const heroScale = useTransform(scrollY, [0, 400], [1, 1.05]);

    return (
        <>
            <Head title="Masak Enak Nusantara, Kenali Gizinya" />

            <div className="relative min-h-screen bg-warm-cream text-charcoal">
                {/* Aksen pendar cahaya lembut di area hero */}
                <div className="pointer-events-none absolute inset-0 z-0 overflow-hidden">
                    <motion.div style={{ y: blobY1 }} className="absolute -left-24 -top-24 h-96 w-96 rounded-full bg-mint-light/40 blur-3xl" />
                    <motion.div style={{ y: blobY2 }} className="absolute -right-24 top-48 h-96 w-96 rounded-full bg-terracotta/10 blur-3xl" />
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
                    <motion.div
                        style={{ y: heroY, opacity: heroOpacity, scale: heroScale, backgroundImage: "url('/images/landing/hero-background.png')" }}
                        className="absolute inset-0 bg-cover bg-center origin-center"
                        aria-hidden="true"
                    />
                    {/* Selimut krem supaya teks tetap terbaca di atas foto */}
                    <div className="absolute inset-0 bg-warm-cream/80" aria-hidden="true" />

                    <motion.div style={{ y: heroY }} className="relative mx-auto w-full max-w-shell px-4 pb-16 pt-24 sm:px-6 lg:px-8 lg:pb-24 lg:pt-28">
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
                    </motion.div>
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
                                <motion.div
                                    key={recipe.slug}
                                    initial={{ opacity: 0, y: 30, scale: 0.95 }}
                                    whileInView={{ opacity: 1, y: 0, scale: 1 }}
                                    viewport={{ once: true, margin: '-50px' }}
                                    transition={{ delay: i * 0.1, type: 'spring', stiffness: 70 }}
                                    whileHover={{ scale: 1.03, y: -8, transition: { type: 'spring', stiffness: 200 } }}
                                    className="h-full"
                                >
                                    <RecipeCard recipe={recipe} labelKategori={labelKategori} />
                                </motion.div>
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
                                    <motion.div
                                        key={entri.name}
                                        initial={{ opacity: 0, y: 30, rotateX: 10 }}
                                        whileInView={{ opacity: 1, y: 0, rotateX: 0 }}
                                        viewport={{ once: true, margin: '-60px' }}
                                        transition={{ delay: i * 0.15, type: 'spring', stiffness: 65 }}
                                        whileHover={{ scale: 1.02, y: -4, rotateZ: 0.5, boxShadow: '0 20px 40px -15px rgba(0,0,0,0.08)' }}
                                        className="h-full"
                                    >
                                        <div className="h-full rounded-2xl border border-border bg-surface p-6 shadow-card">
                                            <motion.div
                                                initial={{ scale: 0.8 }}
                                                whileInView={{ scale: 1 }}
                                                transition={{ delay: i * 0.15 + 0.2, type: 'spring', bounce: 0.5 }}
                                                className={`flex h-11 w-11 items-center justify-center rounded-xl ring-1 ring-inset ring-border ${entri.iconTone}`}
                                            >
                                                <RoleIcon className="h-5 w-5" />
                                            </motion.div>

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
                                    </motion.div>
                                );
                            })}
                        </div>
                    </div>
                </section>

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
                                        <motion.div
                                            key={item.q}
                                            initial={{ opacity: 0, x: 20 }}
                                            whileInView={{ opacity: 1, x: 0 }}
                                            viewport={{ once: true, margin: '-30px' }}
                                            transition={{ delay: i * 0.1 }}
                                        >
                                            <motion.button
                                                type="button"
                                                onClick={() => setOpenFaq(open ? -1 : i)}
                                                aria-expanded={open}
                                                className="flex w-full items-center justify-between gap-4 py-5 text-left"
                                                whileHover={{ x: 4 }}
                                                whileTap={{ scale: 0.98 }}
                                            >
                                                <span
                                                    className={`font-semibold transition ${
                                                        open ? 'text-primary' : 'text-charcoal hover:text-primary'
                                                    }`}
                                                >
                                                    {item.q}
                                                </span>
                                                <motion.div
                                                    animate={{ rotate: open ? 180 : 0 }}
                                                    transition={{ type: 'spring', stiffness: 200 }}
                                                >
                                                    <IconChevron
                                                        className={`h-5 w-5 shrink-0 text-muted ${open ? 'text-primary' : ''}`}
                                                    />
                                                </motion.div>
                                            </motion.button>

                                            <AnimatePresence>
                                                {open && (
                                                    <motion.div
                                                        initial={{ height: 0, opacity: 0 }}
                                                        animate={{ height: 'auto', opacity: 1 }}
                                                        exit={{ height: 0, opacity: 0 }}
                                                        transition={{ duration: 0.3, ease: 'easeOut' }}
                                                        className="overflow-hidden"
                                                    >
                                                        <p className="pb-5 font-body text-sm leading-relaxed text-muted">
                                                            {item.a}
                                                        </p>
                                                    </motion.div>
                                                )}
                                            </AnimatePresence>
                                        </motion.div>
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
