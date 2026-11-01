export default function Checkbox({ className = '', name, checked, ...props }) {
    return (
        <input
            {...props}
            type="checkbox"
            name={name}
            checked={checked}
            className={
                'rounded border-border text-primary shadow-sm focus:ring-primary focus:ring-offset-warm-cream ' +
                className
            }
        />
    );
}
