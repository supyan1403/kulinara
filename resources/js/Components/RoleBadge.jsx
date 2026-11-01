/**
 * Lencana role berbentuk pill (rounded-full).
 *
 * Pemetaan warna ke palet resmi:
 *   - Pengguna  → linen + charcoal        (netral, pengguna biasa)
 *   - Pengelola → mint-light + primary    (otoritas kurasi)
 *
 * Terima string role, atau objek { role, label } bila label sudah dihitung server.
 */
const BADGE_CLASSES = {
    pengguna: 'bg-linen text-charcoal ring-border',
    pengelola: 'bg-mint-light text-primary ring-primary/20',
};

const LABELS = {
    pengguna: 'Pengguna',
    pengelola: 'Pengelola',
};

export default function RoleBadge({ role, label, className = '' }) {
    const value = typeof role === 'string' ? role : role?.value;
    const text = label ?? (typeof role === 'object' ? role?.label : null) ?? LABELS[value] ?? value;
    const tone = BADGE_CLASSES[value] ?? BADGE_CLASSES.pengguna;

    return (
        <span
            className={`inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset ${tone} ${className}`}
        >
            {text}
        </span>
    );
}
