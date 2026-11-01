import SiteHeader from '@/Components/SiteHeader';
import Toast from '@/Components/Toast';
import { usePage } from '@inertiajs/react';

/**
 * Cangkang halaman di dalam aplikasi (setelah masuk).
 *
 * Bilah navigasinya BUKAN milik layout ini: ia dipinjam dari SiteHeader, yang
 * juga dipakai beranda dan /resep. Dulu layout ini punya <nav> sendiri dengan
 * gaya yang berbeda (latar bg-surface, tautan underline, lencana peran),
 * sehingga menekan "Telusuri Resep" — halaman publik — membuat header
 * berubah bentuk: nav in-app lenyap, berganti Simpanan + pil hijau. Satu
 * header untuk semua halaman menghapus perbedaan itu.
 *
 * Yang tersisa di sini hanya hal yang memang khas halaman dalam: wadah Toast
 * dan `header` opsional berisi judul halaman.
 */
export default function AuthenticatedLayout({ header, children, motif = false, overlay = false }) {
    const { auth } = usePage().props;

    return (
        <div className={`min-h-screen ${motif ? 'bg-motif-rempah' : 'bg-warm-cream'}`}>
            <SiteHeader
                user={auth.user}
                canLogin={true}
                canRegister={true}
                beranda={false}
                overlay={overlay}
            />

            <Toast />

            {/*
                Kepala halaman dulu berupa `<header>` putih selebar layar dengan
                `border-b` dan `shadow-card`. Karena bilah navigasi di atasnya
                juga berupa bar penuh dengan bayangan, keduanya terbaca sebagai
                dua bilah bertumpuk — dan begitu navbar diberi sudut membulat,
                bar putih di bawahnya memotong lengkungan itu.

                Sekarang kepala halaman diperlakukan sebagai isi halaman, bukan
                bilah: tanpa latar, tanpa garis, tanpa bayangan. Judul dan
                deskripsi tetap utuh, termasuk tombol aksi yang dipakai
                Studio/Index dan Studio/Form. Yang hilang hanya "bingkainya",
                bukan isinya.
            */}
            {header && (
                <div className="mx-auto max-w-shell px-4 pb-1 pt-7 sm:px-6 lg:px-8">
                    {header}
                </div>
            )}

            <main>{children}</main>
        </div>
    );
}
