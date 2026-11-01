/**
 * Lencana status resep berbentuk pill (rounded-full).
 *
 * Palet mengikuti token desain:
 *   - draft         → linen + muted   (belum tayang)
 *   - diajukan      → terracotta tint (menunggu keputusan Pengelola)
 *   - terbit        → mint + sage     (sudah tayang)
 *   - terverifikasi → primary solid   (sudah dikurasi Pengelola)
 */
const BADGE_CLASSES = {
    draft: 'bg-linen text-muted ring-border',
    diajukan: 'bg-terracotta/10 text-[#9c4526] ring-terracotta/30',
    terbit: 'bg-mint-light text-sage-dark ring-sage-dark/30',
    terverifikasi: 'bg-primary text-white ring-primary/20',
};

const LABELS = {
    draft: 'Draft',
    diajukan: 'Menunggu ditinjau',
    terbit: 'Terbit',
    terverifikasi: 'Terverifikasi',
};

/** Ekspor bersama — dipakai radio status di formulir Studio. */
export const STATUS_LABELS = LABELS;

export default function StatusBadge({ status, className = '' }) {
    const value = typeof status === 'string' ? status : status?.value;
    const text =
        (typeof status === 'object' ? status?.label : null) ??
        LABELS[value] ??
        value;
    const tone = BADGE_CLASSES[value] ?? BADGE_CLASSES.draft;

    return (
        <span
            className={`inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset ${tone} ${className}`}
        >
            {text}
        </span>
    );
}
