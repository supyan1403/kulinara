import { useEffect, useRef, useState } from 'react';

/**
 * Elemen yang muncul perlahan ketika masuk ke viewport.
 *
 * Memakai IntersectionObserver bawaan browser — tanpa dependensi tambahan.
 * Ada dua jaring pengaman supaya konten tidak pernah terkunci transparan:
 *   1. browser tanpa IntersectionObserver  -> langsung tampil
 *   2. pengamat tak pernah memicu          -> tampil juga setelah 10 detik
 *
 * Pengguna dengan preferensi "reduce motion" mendapat elemen tanpa animasi.
 */
export default function Reveal({
    as: Tag = 'div',
    delay = 0,
    className = '',
    children,
}) {
    const rujuk = useRef(null);
    const [terlihat, setTerlihat] = useState(false);

    useEffect(() => {
        const el = rujuk.current;

        if (!el || typeof IntersectionObserver === 'undefined') {
            setTerlihat(true);

            return;
        }

        const pengamat = new IntersectionObserver(
            (entries) => {
                if (entries.some((entry) => entry.isIntersecting)) {
                    setTerlihat(true);
                    pengamat.disconnect();
                }
            },
            { threshold: 0.15 },
        );

        pengamat.observe(el);

        // Jaring pengaman: kalau pengamat tidak pernah memicu (mis. kasus aneh
        // pada browser tertentu), konten tetap ditampilkan — tapi sengaja
        // diberi jeda 10 detik supaya animasi reveal saat scroll tetap terasa.
        const pengaman = setTimeout(() => setTerlihat(true), 10000);

        return () => {
            pengamat.disconnect();
            clearTimeout(pengaman);
        };
    }, []);

    return (
        <Tag
            ref={rujuk}
            data-reveal
            style={delay ? { animationDelay: `${delay}ms` } : undefined}
            className={`${terlihat ? 'animate-fade-up' : 'opacity-0'} motion-reduce:animate-none ${className}`}
        >
            {children}
        </Tag>
    );
}
