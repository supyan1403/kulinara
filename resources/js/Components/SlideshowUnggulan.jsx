import FotoResep from '@/Components/FotoResep';
import { Link } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const INTERVAL_MS = 4000;

/**
 * Etalase hero berupa slideshow resep unggulan.
 *
 * Putaran slide sepenuhnya terjadi di browser — pergantian gambar dan judul
 * tidak memicu request tambahan. Tiga aturan yang dijaga di sini:
 *
 *   1. Kursor atau fokus keyboard di dalam kartu menghentikan putaran,
 *      sesuai WCAG 2.2.2 (konten yang bergerak otomatis wajib bisa dijeda).
 *   2. Pengguna dengan preferensi "reduce motion" tidak mendapat putaran
 *      otomatis sama sekali; mereka memakai dots untuk berpindah sendiri.
 *   3. Tinggi area judul dipesan untuk dua baris, supaya kartu tidak
 *      meloncat walau panjang judul tiap hidangan berbeda-beda.
 *   4. Kartu menunjuk ke halaman detail resep yang sedang tampil, sehingga
 *      hero tidak hanya memamerkan hidangan — pengunjung benar-benar bisa
 *      membacanya.
 */
export default function SlideshowUnggulan({ resep = [] }) {
    // Index awal diacak supaya tiap kunjungan memulai dari hidangan berbeda.
    const [indeks, setIndeks] = useState(() =>
        resep.length > 0 ? Math.floor(Math.random() * resep.length) : 0,
    );
    const [jeda, setJeda] = useState(false);
    const kurangiGerak = useRef(false);

    useEffect(() => {
        kurangiGerak.current =
            typeof window !== 'undefined' &&
            typeof window.matchMedia === 'function' &&
            window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }, []);

    useEffect(() => {
        if (jeda || resep.length < 2 || kurangiGerak.current) {
            return;
        }

        const timer = setInterval(
            () => setIndeks((i) => (i + 1) % resep.length),
            INTERVAL_MS,
        );

        return () => clearInterval(timer);
    }, [jeda, resep.length]);

    const aktif = resep[indeks];

    if (!aktif) {
        return null;
    }

    return (
        // `max-w-lg` (bukan `max-w-md` lama) — hero kini menempati satu layar
        // penuh, dan pada ketinggian itu kartu selebar 448px meninggalkan
        // terlalu banyak udara di kolom kanan.
        <div
            className="mx-auto w-full max-w-lg"
            onMouseEnter={() => setJeda(true)}
            onMouseLeave={() => setJeda(false)}
            onFocusCapture={() => setJeda(true)}
            onBlurCapture={() => setJeda(false)}
        >
            {/* Seluruh kartu adalah tautan menuju resep yang sedang aktif —
                halaman detailnya sudah ada, dan beranda dulu memamerkan
                ratusan resep yang tidak satu pun bisa dibuka. Titik navigasi
                sengaja berada DI LUAR tautan ini, agar mengkliknya tidak
                ikut memindahkan halaman. */}
            <Link
                href={route('resep.show', aktif.slug)}
                className="block overflow-hidden rounded-2xl bg-surface p-4 text-charcoal shadow-overlay focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
            >
                {/* Semua slide ditumpuk lapis demi lapis; yang aktif menyala,
                    sisanya memudar. Karena tidak ada pergeseran, kartu tidak
                    pernah terasa bergetar. */}
                <div className="relative aspect-[4/3] overflow-hidden rounded-xl bg-linen">
                    {resep.map((r, i) => (
                        <div
                            key={r.slug}
                            aria-hidden={i !== indeks}
                            className={`absolute inset-0 transition-opacity duration-700 ease-out motion-reduce:transition-none ${
                                i === indeks ? 'opacity-100' : 'opacity-0'
                            }`}
                        >
                            <FotoResep recipe={r} eager={i === 0} />
                        </div>
                    ))}

                    <span className="absolute left-3 top-3 z-10 rounded-full bg-surface/90 px-2.5 py-1 text-[11px] font-semibold text-charcoal backdrop-blur-sm ring-1 ring-inset ring-border">
                        Resep Unggulan
                    </span>
                </div>

                <div className="mt-4 min-h-[5rem]">
                    <h3 className="line-clamp-2 text-xl font-bold leading-snug">
                        {aktif.judul}
                    </h3>
                    <p className="mt-1 font-body text-sm text-muted">
                        Resep autentik oleh{' '}
                        <span className="font-semibold text-primary">
                            {aktif.penulis?.name ?? 'Penulis Kulinara'}
                        </span>
                    </p>
                </div>
            </Link>

            {resep.length > 1 && (
                <div
                    className="mt-4 flex justify-center gap-2"
                    role="group"
                    aria-label="Pilih resep unggulan"
                >
                    {resep.map((r, i) => (
                        <button
                            key={r.slug}
                            type="button"
                            onClick={() => setIndeks(i)}
                            aria-label={`Tampilkan ${r.judul}`}
                            aria-current={i === indeks}
                            className={`h-2.5 w-2.5 rounded-full transition ${
                                i === indeks
                                    ? 'bg-primary'
                                    : 'bg-border hover:bg-sage-dark/50'
                            }`}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}
