import { router } from '@inertiajs/react';

const WAKTU = [
    { value: '', label: 'Semua durasi' },
    { value: '15', label: '≤ 15 menit' },
    { value: '30', label: '≤ 30 menit' },
    { value: '60', label: '≤ 60 menit' },
    { value: '90', label: '≤ 90 menit' },
];

const RATING = [
    { value: '', label: 'Semua rating' },
    { value: '4.5', label: '≥ 4,5' },
    { value: '4', label: '≥ 4,0' },
    { value: '3', label: '≥ 3,0' },
];

const kelasSelect =
    'w-full rounded-lg border-border bg-surface py-2.5 pl-3 pr-8 font-body text-sm text-charcoal shadow-sm focus:border-primary focus:ring-primary focus:ring-offset-warm-cream';

/**
 * Saringan lanjutan direktori resep: tingkat kesulitan, durasi maksimal,
 * rating minimum, dan rujukan bahan. Semua saringan digabungkan lewat query
 * string, dan `page` sengaja tidak pernah ikut dikirim — mengganti saringan
 * berarti memulai dari halaman pertama lagi.
 */
export default function FilterResep({
    tujuan = '/resep',
    kataCari = '',
    kategoriAktif = null,
    urutkan = 'terbaru',
    kesulitan = '',
    kesulitanTersedia = [],
    maksMenit = '',
    minRating = '',
    bahanId = null,
    bahanTersedia = [],
}) {
    // Saringan yang dipertahankan lintas perubahan filter: kata kunci,
    // kategori, dan urutan tidak boleh hilang saat pengunjung menyaring.
    const dasar = () => ({
        ...(kataCari ? { q: kataCari } : {}),
        ...(kategoriAktif ? { kategori: kategoriAktif } : {}),
        ...(urutkan !== 'terbaru' ? { urutkan } : {}),
    });

    const buka = (perubahan) => {
        router.get(
            tujuan,
            { ...dasar(), ...perubahan },
            { preserveState: true, preserveScroll: true },
        );
    };

    const adaSaringan = Boolean(kesulitan || maksMenit || minRating || bahanId);

    return (
        <div className="mt-6 rounded-2xl border border-border bg-surface p-4 sm:p-5">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <p className="font-body text-sm font-semibold text-charcoal">
                    Saring lebih lanjut
                </p>
                {adaSaringan && (
                    <button
                        type="button"
                        onClick={() => buka({})}
                        className="font-body text-xs font-semibold text-muted transition hover:text-terracotta"
                    >
                        Reset saringan
                    </button>
                )}
            </div>

            <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <label className="block">
                    <span className="mb-1.5 block font-body text-xs font-semibold text-muted">
                        Tingkat kesulitan
                    </span>
                    <select
                        value={kesulitan}
                        onChange={(e) => buka(e.target.value ? { kesulitan: e.target.value } : {})}
                        className={kelasSelect}
                    >
                        <option value="">Semua tingkat</option>
                        {kesulitanTersedia.map((k) => (
                            <option key={k.value} value={k.value}>
                                {k.label}
                            </option>
                        ))}
                    </select>
                </label>

                <label className="block">
                    <span className="mb-1.5 block font-body text-xs font-semibold text-muted">
                        Durasi masak
                    </span>
                    <select
                        value={maksMenit}
                        onChange={(e) => buka(e.target.value ? { maks_menit: e.target.value } : {})}
                        className={kelasSelect}
                    >
                        {WAKTU.map((w) => (
                            <option key={w.value} value={w.value}>
                                {w.label}
                            </option>
                        ))}
                    </select>
                </label>

                <label className="block">
                    <span className="mb-1.5 block font-body text-xs font-semibold text-muted">
                        Rating minimum
                    </span>
                    <select
                        value={minRating}
                        onChange={(e) => buka(e.target.value ? { min_rating: e.target.value } : {})}
                        className={kelasSelect}
                    >
                        {RATING.map((r) => (
                            <option key={r.value} value={r.value}>
                                {r.label}
                            </option>
                        ))}
                    </select>
                </label>

                <label className="block">
                    <span className="mb-1.5 block font-body text-xs font-semibold text-muted">
                        Mengandung bahan
                    </span>
                    <select
                        value={bahanId ?? ''}
                        onChange={(e) =>
                            buka(e.target.value ? { bahan_id: e.target.value } : {})
                        }
                        className={kelasSelect}
                    >
                        <option value="">Semua bahan</option>
                        {bahanTersedia.map((b) => (
                            <option key={b.id} value={b.id}>
                                {b.nama}
                            </option>
                        ))}
                    </select>
                </label>
            </div>
        </div>
    );
}
