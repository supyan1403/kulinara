import { IconSearch } from '@/Components/Icons';
import { router } from '@inertiajs/react';
import { useState } from 'react';

/**
 * Kolom pencarian. Satu bentuk yang sama dipakai di beranda dan di /resep.
 *
 * `tujuan` adalah rute yang menerima saringan: "/" untuk beranda, "/resep"
 * untuk direktori. `page` sengaja tidak pernah ikut dikirim — mengetik kata
 * kunci baru berarti memulai hasil dari halaman pertama lagi, bukan
 * melanjutkan ke halaman lama yang sudah tidak relevan.
 */
export default function FormPencarian({ tujuan = '/', kataCari = '', kategoriAktif = null }) {
    const [nilai, setNilai] = useState(kataCari);

    const kirim = (e) => {
        e.preventDefault();

        router.get(
            tujuan,
            {
                ...(kategoriAktif ? { kategori: kategoriAktif } : {}),
                ...(nilai.trim() ? { q: nilai.trim() } : {}),
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <form className="flex max-w-lg flex-col gap-2 sm:flex-row" onSubmit={kirim} role="search">
            <div className="relative flex-1">
                <IconSearch className="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-muted" />
                <input
                    type="search"
                    value={nilai}
                    onChange={(e) => setNilai(e.target.value)}
                    placeholder="Cari resep atau bahan…"
                    aria-label="Cari resep"
                    className="w-full rounded-lg border-0 bg-surface py-3 pl-11 pr-4 font-body text-sm text-charcoal ring-1 ring-inset ring-border placeholder:text-muted focus:bg-surface focus:ring-2 focus:ring-primary"
                />
            </div>
            <button
                type="submit"
                className="rounded-lg bg-primary px-6 py-3 text-sm font-semibold text-white shadow-raised transition hover:bg-primary-dark"
            >
                Cari Resep
            </button>
        </form>
    );
}
