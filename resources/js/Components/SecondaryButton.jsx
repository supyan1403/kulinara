export default function SecondaryButton({
    type = 'button',
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            type={type}
            className={
                `inline-flex items-center rounded-lg border border-border bg-surface px-4 py-2 text-xs font-semibold uppercase tracking-widest text-charcoal shadow-sm transition duration-150 ease-in-out hover:bg-linen focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-warm-cream disabled:opacity-25 ${
                    disabled && 'opacity-25'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
