import Logo from '@/Components/Logo';
import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';

/**
 * Halaman kredit foto.
 *
 * Hampir seluruh foto hidangan berasal dari Wikimedia Commons dengan lisensi
 * Creative Commons Attribution-ShareAlike. Syarat lisensinya mewajibkan
 * mencantumkan nama pembuat, tautan sumber, dan keterangan bahwa gambar
 * mengalami perubahan — halaman inilah tempat kewajiban itu dipenuhi.
 *
 * Daftarnya dikirim dari manifest hasil script unduh-foto.mjs, sehingga tidak
 * pernah bisa tertinggal ketika dataset resep bertambah.
 */

const Svg = ({ children, className = 'h-4 w-4' }) => (
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

export default function KreditFoto({ jumlahFoto, jumlahLisensi, foto }) {
    const [kataCari, setKataCari] = useState('');

    const kata = kataCari.trim().toLowerCase();

    const hasil = kata
        ? foto.filter((f) => `${f.judul} ${f.penulis}`.toLowerCase().includes(kata))
        : foto;

    return (
        <>
            <Head title="Kredit Foto" />

            <div className="min-h-screen bg-warm-cream">
                {/* ───────── Kepala halaman ───────── */}
                <header className="border-b border-border">
                    <div className="mx-auto flex max-w-shell items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                        <Link href="/" className="flex items-center gap-2">
                            <Logo className="h-8 w-8" />
                            <span className="text-lg font-extrabold tracking-tight text-primary">
                                kulinara<span className="text-terracotta">.</span>
                            </span>
                        </Link>

                        <Link
                            href="/"
                            className="font-body text-sm text-muted transition hover:text-primary"
                        >
                            ← Kembali ke beranda
                        </Link>
                    </div>
                </header>

                <main className="mx-auto max-w-shell px-4 py-12 sm:px-6 lg:px-8">
                    {/* ───────── Penjelasan ───────── */}
                    <h1 className="text-3xl font-extrabold tracking-tight text-charcoal sm:text-4xl">
                        Kredit Foto
                    </h1>

                    <p className="mt-4 max-w-3xl font-body leading-relaxed text-muted">
                        Hidangan di Kulinara diilustrasikan dengan foto asli dari Wikimedia
                        Commons. Sebagian besar berlisensi{' '}
                        <span className="font-semibold text-charcoal">
                            Creative Commons Attribution-ShareAlike
                        </span>
                        , yang mensyaratkan pencantuman nama pembuat, tautan ke sumber, dan
                        penandaan bila gambar mengalami perubahan. Halaman ini memenuhi kewajiban
                        tersebut: setiap foto disertai pembuatnya dan tautan ke berkas asli.
                        Foto yang digunakan pada halaman ini berukuran 900 piksel, diubah dari
                        berkas sumbernya.
                    </p>

                    {/* ───────── Ringkasan lisensi ───────── */}
                    <div className="mt-8 flex flex-wrap items-center gap-2">
                        <span className="font-mono text-xs uppercase tracking-wider text-muted">
                            {jumlahFoto} foto
                        </span>

                        {jumlahLisensi.map((l) => (
                            <span
                                key={l.nama}
                                className="inline-flex items-center gap-1.5 rounded-full bg-mint-light px-3 py-1 font-mono text-xs font-semibold text-primary ring-1 ring-inset ring-primary/15"
                            >
                                {l.nama}
                                <span className="text-primary/60">{l.jumlah}</span>
                            </span>
                        ))}
                    </div>

                    {/* ───────── Saring ───────── */}
                    <div className="mt-8">
                        <label htmlFor="cari-foto" className="sr-only">
                            Cari hidangan atau pembuat foto
                        </label>
                        <input
                            id="cari-foto"
                            type="search"
                            value={kataCari}
                            onChange={(e) => setKataCari(e.target.value)}
                            placeholder="Cari hidangan atau pembuat…"
                            className="w-full max-w-md rounded-xl border border-border bg-surface px-4 py-2.5 font-body text-sm text-charcoal placeholder:text-muted focus:border-primary focus:ring-primary"
                        />
                    </div>

                    {/* ───────── Daftar ───────── */}
                    <div className="mt-6 overflow-hidden rounded-2xl border border-border bg-surface shadow-card">
                        <table className="w-full text-left">
                            <thead className="border-b border-border bg-linen">
                                <tr className="font-mono text-[11px] uppercase tracking-wider text-muted">
                                    <th scope="col" className="px-4 py-3 font-semibold">
                                        Hidangan
                                    </th>
                                    <th scope="col" className="px-4 py-3 font-semibold">
                                        Pembuat
                                    </th>
                                    <th scope="col" className="hidden px-4 py-3 font-semibold sm:table-cell">
                                        Lisensi
                                    </th>
                                    <th scope="col" className="px-4 py-3 text-right font-semibold">
                                        Sumber
                                    </th>
                                </tr>
                            </thead>

                            <tbody className="divide-y divide-border">
                                {hasil.map((f) => (
                                    <tr key={f.halaman} className="align-top">
                                        <td className="px-4 py-3 font-body text-sm text-charcoal">
                                            {f.judul}
                                            <span className="mt-1 block font-mono text-[11px] text-muted sm:hidden">
                                                {f.lisensi}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 font-body text-sm text-muted">
                                            {f.penulis}
                                        </td>
                                        <td className="hidden px-4 py-3 font-mono text-xs text-muted sm:table-cell">
                                            {f.lisensi}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <a
                                                href={f.halaman}
                                                target="_blank"
                                                rel="noreferrer noopener"
                                                className="inline-flex items-center gap-1 font-body text-sm font-medium text-primary underline-offset-4 hover:underline"
                                            >
                                                Commons
                                                <Svg>
                                                    <path d="M14 4h6v6" />
                                                    <path d="M20 4l-8.5 8.5" />
                                                    <path d="M19 14v5a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h5" />
                                                </Svg>
                                            </a>
                                        </td>
                                    </tr>
                                ))}

                                {hasil.length === 0 && (
                                    <tr>
                                        <td colSpan={4} className="px-4 py-12 text-center">
                                            <p className="font-body text-sm text-muted">
                                                Tidak ada foto yang cocok dengan “{kataCari}”.
                                            </p>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    <p className="mt-6 font-mono text-[11px] leading-relaxed text-muted">
                        Perubahan yang dilakukan: pemangkasan ukuran ke 900 piksel lebar.
                        Tidak ada penyuntingan warna atau konten.
                    </p>
                </main>
            </div>
        </>
    );
}
