/**
 * Tombol utama — peran "tombol utama & header" sesuai dokumen Brand Essentials.
 * Fokus memakai ring mint-spark sebagai aksen verifikasi.
 */
export default function PrimaryButton({
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            className={
                `inline-flex items-center rounded-lg bg-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-mint-spark focus:ring-offset-2 focus:ring-offset-warm-cream active:bg-primary-dark ${
                    disabled && 'opacity-25'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
