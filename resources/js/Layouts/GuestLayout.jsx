import Logo from '@/Components/Logo';
import { Link } from '@inertiajs/react';

/**
 * Layout untuk seluruh halaman auth (masuk, daftar, lupa/reset password,
 * konfirmasi, verifikasi).
 *
 * Dua panel berdampingan: panel merek di kiri (foto masakan + logo), panel
 * formulir di kanan. Keduanya berada dalam satu tinggi layar yang KETAT —
 * halaman auth tidak boleh bisa digulir sama sekali, sehingga `overflow-hidden`
 * dipasang di akar dan tinggi halaman tidak pernah melebihi tinggi viewport.
 *
 * `dvh` dipakai, bukan `vh`. Di ponsel `100vh` ikut menghitung bilah alamat
 * yang menyusut ketika pengguna menggulir ke bawah, sehingga isi halaman
 * terpotong beberapa puluh piksel di bawah; `100dvh` mengikuti tinggi yang
 * benar-benar terlihat. Tailwind 3.2 belum menyediakan utilitas `h-dvh`
 * (baru dari 3.4), jadi nilai arbitrer yang dipakai.
 *
 * Konsekuensinya nyata: setiap halaman anak harus memastikan isinya muat.
 * Karena itu Register dipecah menjadi dua langkah — lihat catatannya di sana.
 */
export default function GuestLayout({ children, statistik = null }) {
    return (
        <div className="flex h-[100dvh] overflow-hidden bg-warm-cream">
            {/* ── Panel merek: hanya di layar lebar, di ponsel tidak ikut ── */}
            <aside className="relative hidden w-[44%] max-w-[600px] shrink-0 overflow-hidden bg-primary lg:block">
                {/*
                    Foto diletakkan di bawah tiga lapis, dan arahnya penting.

                    Versi pertama memakai `opacity-25` di bawah gradien
                    `via-primary/90 to-primary-dark` — dua lapis gelap yang
                    saling menguatkan, sehingga panel lurus menjadi hijau
                    polos dan fotonya terbuang percuma.

                    Sekarang gradiennya BERARAH: horizontal menahan kekeruhan
                    di kiri tempat teks berdiri lalu menipis ke kanan, dan
                    vertikal menggelapkan dasar tempat tautan duduk. Teks
                    tidak pernah menimpa bagian foto yang paling terang.
                */}
                <img
                    src="/images/landing/rendang.jpg"
                    alt=""
                    aria-hidden="true"
                    className="absolute inset-0 h-full w-full object-cover opacity-60"
                />
                <div className="absolute inset-0 bg-gradient-to-r from-primary via-primary/90 to-primary/55" />
                <div className="absolute inset-0 bg-gradient-to-t from-primary-dark/70 via-transparent to-primary/40" />

                {/* Belah ketupat rempah — pengulangan motif dari logo */}
                <svg
                    aria-hidden="true"
                    viewBox="0 0 24 24"
                    className="pointer-events-none absolute -right-16 -top-16 h-72 w-72 text-mint-spark/10"
                >
                    <path
                        d="M12 2.5L13.2 4.5L15 5.5L13.2 6.5L12 8.5L10.8 6.5L9 5.5L10.8 4.5Z"
                        fill="currentColor"
                        transform="translate(0 3) scale(2.8)"
                    />
                </svg>

                <div className="relative flex h-full flex-col justify-between p-10 xl:p-14">
                    <Logo tone="dark" withWordmark className="h-11 w-11" />

                    <div className="max-w-md">
                        <p className="font-mono text-xs uppercase tracking-[0.2em] text-mint-light">
                            Direktori kuliner Nusantara
                        </p>

                        <h2 className="mt-4 text-4xl font-extrabold leading-tight tracking-tight text-white xl:text-5xl">
                            Masak enak,
                            <br />
                            kenali gizinya.
                        </h2>

                        <p className="mt-5 font-body text-base leading-relaxed text-mint-light">
                            Setiap resep datang dengan perkiraan kalori dan
                            makronutrisi per porsi, mengacu Tabel Komposisi
                            Pangan Indonesia. Cukup satu akun untuk mulai
                            menyimpan dan memasak.
                        </p>

                        {/*
                            Blok ini hanya muncul bila halaman anak
                            mengirimkannya. Halaman masuk TIDAK mengirim —
                            orang di sana sedang mengetik emailnya, sudah lewat
                            titik di mana statistik masih meyakinkan, dan
                            panelnya justru lebih lapang tanpa blok ini.

                            Angka datang dari controller, bukan tertulis di
                            sini. Versi sebelumnya menulis 385/6/368 langsung
                            ke JSX, sehingga begitu resep bertambah atau
                            ditarik blok ini berbohong diam-diam tanpa
                            peringatan.
                        */}
                        {statistik?.length > 0 && (
                            <dl className="mt-10 grid max-w-sm grid-cols-2 gap-6 border-t border-white/15 pt-6">
                                {statistik.map(({ angka, label }) => (
                                    <div key={label}>
                                        <dt className="font-mono text-2xl font-bold tabular-nums text-white">
                                            {angka}
                                        </dt>
                                        {/*
                                            mint-light dipakai TANPA opasitas
                                            parsial. Dua gradien di atas hanya
                                            bisa menggelapkan foto, tak pernah
                                            mencerahkannya, jadi kasus terburuk
                                            bagi teks terang adalah foto putih
                                            murni — dan terhadap itu /70 jatuh
                                            ke 3,7:1, di bawah ambang AA 4,5:1
                                            untuk teks 12px. Dengan opasitas
                                            penuh angkanya 5,3:1, lolos terhadap
                                            foto apa pun.
                                        */}
                                        <dd className="mt-0.5 text-xs uppercase tracking-wider text-mint-light">
                                            {label}
                                        </dd>
                                    </div>
                                ))}
                            </dl>
                        )}
                    </div>

                    <Link
                        href="/"
                        className="inline-flex w-fit items-center gap-2 text-sm font-semibold text-mint-light underline-offset-4 transition hover:text-white hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-mint-spark focus-visible:ring-offset-4 focus-visible:ring-offset-primary"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            className="h-4 w-4"
                            aria-hidden="true"
                        >
                            <path d="M19 12H5" />
                            <path d="M12 19l-7-7 7-7" />
                        </svg>
                        Kembali ke Beranda
                    </Link>
                </div>
            </aside>

            {/* ── Panel formulir ── */}
            <div className="relative flex min-w-0 flex-1 flex-col overflow-hidden">
                <header className="flex shrink-0 items-center justify-between gap-4 px-5 py-4 sm:px-8 sm:py-5">
                    <Link
                        href="/"
                        className="flex items-center gap-2.5 lg:invisible"
                        aria-label="Beranda kulinara"
                    >
                        <Logo className="h-9 w-9" />
                        <span className="text-xl font-extrabold tracking-tight text-primary">
                            kulinara
                        </span>
                    </Link>

                    {/*
                        Tombol ini selalu terlihat di semua ukuran layar, tidak
                        hanya di panel merek — di ponsel panel itu tidak ada,
                        dan permintaan "ada tombol kembali ke beranda" tidak
                        boleh bergantung pada lebar layar.
                    */}
                    <Link
                        href="/"
                        className="inline-flex shrink-0 items-center gap-2 rounded-full border border-border bg-surface px-3.5 py-2 text-xs font-semibold text-charcoal shadow-sm transition hover:border-primary hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-warm-cream"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            className="h-3.5 w-3.5"
                            aria-hidden="true"
                        >
                            <path d="M19 12H5" />
                            <path d="M12 19l-7-7 7-7" />
                        </svg>
                        Kembali ke Beranda
                    </Link>
                </header>

                <main className="flex min-h-0 flex-1 items-center justify-center px-5 pb-6 sm:px-8">
                    <div className="w-full max-w-md rounded-2xl border border-border bg-surface p-5 shadow-card sm:p-8">
                        {children}
                    </div>
                </main>
            </div>
        </div>
    );
}
