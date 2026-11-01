import { Link } from '@inertiajs/react';

/**
 * Tautan di dalam menu seluler.
 *
 * Bentuknya sengaja "pil" dengan jarak sisi kiri-kanan yang sama, bukan teks
 * yang ditempeli garis tepi. Versi sebelumnya memakai `border-l-4 ps-3`, dan
 * hasilnya di layar sempit: huruf pertama tautan hampir menempel tepi kiri
 * kartu, sementara menu akun di bawahnya punya jarak `px-4`. Dua ukuran jarak
 * berbeda dalam satu panel terbaca sebagai kelalaian, bukan pilihan.
 *
 * `mx-2` memberi napas dari tepi layar supaya latar hover tidak menyentuh
 * pinggir; `px-3.5` menahan teks agar tidak menempel pada sisi pil.
 */
export default function ResponsiveNavLink({
    active = false,
    className = '',
    children,
    ...props
}) {
    return (
        <Link
            {...props}
            className={`mx-2 flex items-center rounded-lg px-3.5 py-2.5 ${
                active
                    ? 'bg-mint-light font-semibold text-primary hover:bg-mint-light focus:bg-mint-light'
                    : 'text-charcoal hover:bg-linen focus:bg-linen focus:text-charcoal'
            } text-[15px] font-medium leading-snug transition duration-150 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-inset ${className}`}
        >
            {children}
        </Link>
    );
}
