import { router } from '@inertiajs/react';

/**
 * Deretan saringan kategori, lengkap dengan pill hapus pencarian.
 *
 * Sama dipakai di beranda dan di /resep; hanya `tujuan` yang berbeda.
 * `diAtasGelap` dibutuhkan karena beranda kini berlatar hijau tua: chip aktif
 * yang tetap `bg-primary` akan larut ke dalam latarnya sendiri. Di atas latar
 * gelap keadaannya dibalik — yang aktif jadi putih penuh, yang tidak aktif
 * jadi kaca tipis supaya tidak bersaing menarik perhatian.
 *
 * Menekan chip yang sedang aktif melepas saringannya, dan `page` tidak pernah
 * ikut dikirim — mengganti kategori berarti kembali ke halaman pertama,
 * bukan melanjutkan ke halaman yang sekarang mungkin sudah di luar jangkauan.
 */
export default function ChipKategori({
    tujuan = '/',
    kategoriTersedia = [],
    kategoriAktif = null,
    kataCari = '',
    diAtasGelap = false,
}) {
    const pilih = (value) => {
        router.get(
            tujuan,
            {
                ...(value && value !== kategoriAktif ? { kategori: value } : {}),
                ...(kataCari ? { q: kataCari } : {}),
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    // Berbeda dari pilih(): pill ini melepas KATA KUNCI, bukan kategori, jadi
    // saringan kategori harus dipaksakan tetap ikut — tidak boleh memakai
    // pilih(), yang justru melepasnya karena nilai keduanya sama.
    const hapusCari = () => {
        router.get(
            tujuan,
            kategoriAktif ? { kategori: kategoriAktif } : {},
            { preserveState: true, preserveScroll: true },
        );
    };

    const gaya = (aktif) => {
        const [kelasAktif, kelasDiam] = diAtasGelap
            ? ['bg-surface text-primary ring-surface', 'bg-white/10 text-white ring-white/25 hover:bg-white/20']
            : ['bg-primary text-white ring-primary', 'bg-surface text-charcoal ring-border hover:bg-linen'];

        return `rounded-full px-4 py-2 text-sm font-semibold ring-1 ring-inset transition ${
            aktif ? kelasAktif : kelasDiam
        }`;
    };

    return (
        <div className="mt-8 flex flex-wrap gap-2">
            {kataCari && (
                <button
                    type="button"
                    onClick={hapusCari}
                    aria-pressed="false"
                    className={`inline-flex items-center gap-1.5 rounded-full px-4 py-2 text-sm font-semibold ring-1 ring-inset transition ${
                        diAtasGelap
                            ? 'bg-surface text-primary ring-surface hover:bg-mint-light'
                            : 'bg-primary text-white ring-primary hover:bg-primary-dark'
                    }`}
                >
                    Kata kunci “{kataCari}”
                    <svg
                        className="h-3.5 w-3.5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2.5"
                        aria-hidden="true"
                    >
                        <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span className="sr-only">Hapus pencarian</span>
                </button>
            )}

            <button type="button" onClick={() => pilih(null)} aria-pressed={!kategoriAktif} className={gaya(!kategoriAktif)}>
                Semua Resep
            </button>

            {kategoriTersedia.map((item) => (
                <button
                    key={item.value}
                    type="button"
                    onClick={() => pilih(item.value)}
                    aria-pressed={item.value === kategoriAktif}
                    className={gaya(item.value === kategoriAktif)}
                >
                    {item.label}
                </button>
            ))}
        </div>
    );
}
