/**
 * Logo resmi KULINARA — "Modern Cloche & Gastronomy Spark".
 *
 * Path SVG mengikuti dokumen Brand & Design Essentials v1.0 secara persis:
 *   1. Diamond spark mint (#10b981) menempel tegak di atas kubah sebagai
 *      simbol rempah permata — warnanya tidak boleh dimodifikasi.
 *   2. Kubah tudung saji (cloche dome).
 *   3. Handle di puncak kubah.
 *   4. Rim piring sebagai garis dasar.
 *
 * `tone` menentukan warna kubah saja:
 *   - 'light' → kubah hijau #044d3c  (kanvas terang)
 *   - 'dark'  → kubah putih #ffffff   (kanvas gelap / hero)
 *
 * `className` selalu diterapkan ke SVG agar pemanggil lama
 * (mis. <Logo className="h-8 w-8" />) tetap bekerja.
 */
export default function Logo({
    className = 'h-7 w-7',
    tone = 'light',
    withWordmark = false,
}) {
    const main = tone === 'dark' ? '#ffffff' : '#044d3c';

    const mark = (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            className={className}
            aria-hidden="true"
        >
            {/* diamond spark — selalu mint, larangan modifikasi */}
            <path
                d="M12 2.5L13.2 4.5L15 5.5L13.2 6.5L12 8.5L10.8 6.5L9 5.5L10.8 4.5Z"
                fill="#10b981"
                stroke="#10b981"
                strokeWidth="0.8"
            />
            {/* kubah cloche */}
            <path
                d="M4 17C4.5 11 8 8.5 12 8.5C16 8.5 19.5 11 20 17H4Z"
                stroke={main}
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            {/* handle */}
            <path d="M11 7.5H13" stroke={main} strokeWidth="2.2" strokeLinecap="round" />
            {/* rim piring */}
            <path d="M2.5 19H21.5" stroke={main} strokeWidth="2.2" strokeLinecap="round" />
        </svg>
    );

    if (!withWordmark) return mark;

    return (
        <span className="inline-flex items-center gap-2.5">
            {mark}
            <span
                className={`text-xl font-extrabold tracking-tight ${
                    tone === 'dark' ? 'text-white' : 'text-primary'
                }`}
            >
                kulinara
            </span>
        </span>
    );
}
