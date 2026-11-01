import { Link } from '@inertiajs/react';

/**
 * Navigasi halaman untuk direktori /resep.
 *
 * Bentuk datanya paginator Laravel (data/links/current_page), yang sudah
 * di-serialize otomatis oleh Inertia. Label "« Previous" bawaan Laravel
 * tidak dipakai — tombol panahnya sendiri, sedangkan `links` hanya
 * menyumbang angka halaman.
 *
 * Angka halaman dibatasi jendela tiga sekitar halaman aktif (plus ujung
 * pertama dan terakhir). Tanpa pembatasan ini direktori dengan tiga puluh
 * halaman akan menampilkan tiga puluh tombol berjajar di satu baris.
 */
export default function Pagination({ halaman }) {
    if (!halaman || halaman.last_page <= 1) {
        return null;
    }

    const { current_page, last_page, from, to, total, prev_page_url, next_page_url } = halaman;

    const angka = halaman.links.filter((l) => /^\d+$/.test(l.label));
    const terlihat = angka.filter((l) => {
        const n = Number(l.label);
        return n === 1 || n === last_page || Math.abs(n - current_page) <= 1;
    });

    const baris = [];
    terlihat.forEach((l, i) => {
        const n = Number(l.label);

        if (i > 0 && n - Number(terlihat[i - 1].label) > 1) {
            baris.push({ key: `gap-${n}`, label: '…' });
        }

        baris.push({ key: `hal-${n}`, label: l.label, url: l.url });
    });

    return (
        <nav className="mt-10 flex flex-col items-center gap-4 sm:flex-row sm:justify-between" aria-label="Navigasi halaman">
            <p className="font-mono text-xs text-muted">
                Menampilkan {from}–{to} dari {total} resep
            </p>

            <div className="flex items-center gap-1.5">
                <Tombol url={prev_page_url} label="Sebelumnya">
                    ‹
                </Tombol>

                {baris.map((item) =>
                    item.url ? (
                        <Link
                            key={item.key}
                            href={item.url}
                            className="rounded-lg px-3 py-1.5 font-mono text-sm text-charcoal ring-1 ring-inset ring-border transition hover:bg-linen"
                        >
                            {item.label}
                        </Link>
                    ) : (
                        <span key={item.key} className="px-2 font-mono text-sm text-muted">
                            {item.label}
                        </span>
                    ),
                )}

                <Tombol url={next_page_url} label="Berikutnya">
                    ›
                </Tombol>
            </div>
        </nav>
    );
}

function Tombol({ url, label, children }) {
    if (!url) {
        return (
            <span
                aria-hidden="true"
                className="rounded-lg px-3 py-1.5 font-mono text-sm text-muted/40 ring-1 ring-inset ring-border/60"
            >
                {children}
            </span>
        );
    }

    return (
        <Link
            href={url}
            aria-label={label}
            className="rounded-lg px-3 py-1.5 font-mono text-sm text-charcoal ring-1 ring-inset ring-border transition hover:bg-linen"
        >
            {children}
        </Link>
    );
}
