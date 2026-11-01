import { IconClock, IconPhoto } from '@/Components/Icons';

const bulat = (nilai) => Math.round(Number(nilai) || 0);

/**
 * Kartu pratinjau resep — meniru tampilan yang dilihat penikmat
 * di halaman utama, agar kreator tahu hasil akhirnya sebelum menerbitkan.
 */
export default function ResepPreview({ data, src = null, kategoriTersedia = [] }) {
    const kategori = kategoriTersedia.find((k) => k.value === data.kategori)?.label;
    // `daerah` sengaja tidak ditampilkan, sama seperti di kartu resep.
    const tag = [kategori].filter(Boolean);

    return (
        <article className="flex flex-col overflow-hidden rounded-2xl border border-border bg-surface shadow-card">
            <div className="relative aspect-[4/3] overflow-hidden bg-linen">
                {src ? (
                    <img
                        src={src}
                        alt={data.judul || 'Pratinjau resep'}
                        className="h-full w-full object-cover"
                    />
                ) : (
                    <div className="flex h-full w-full flex-col items-center justify-center gap-2 text-muted">
                        <IconPhoto className="h-8 w-8" />
                        <span className="font-body text-xs">Belum ada gambar</span>
                    </div>
                )}

                <span className="absolute left-3 top-3 inline-flex items-center gap-1.5 rounded-full bg-surface/90 px-2.5 py-1 text-xs font-semibold text-charcoal backdrop-blur-sm ring-1 ring-inset ring-border">
                    <IconClock className="h-3.5 w-3.5 text-primary" />
                    {data.waktu_masak_menit || 0} mnt
                </span>
            </div>

            <div className="flex flex-1 flex-col p-5">
                {tag.length > 0 && (
                    <div className="flex flex-wrap gap-1.5">
                        {tag.map((t) => (
                            <span
                                key={t}
                                className="rounded-full bg-linen px-2.5 py-0.5 text-[11px] font-semibold text-charcoal ring-1 ring-inset ring-border"
                            >
                                {t}
                            </span>
                        ))}
                    </div>
                )}

                <h3 className="mt-3 text-base font-bold leading-snug text-charcoal">
                    {data.judul || 'Judul resep Anda'}
                </h3>
                <p className="mb-4 mt-1.5 font-body text-sm leading-relaxed text-muted">
                    {data.deskripsi ||
                        'Deskripsi singkat resep akan tampil di sini.'}
                </p>

                <div className="mt-auto flex items-center gap-2 border-t border-border pt-3">
                    <span className="font-mono text-sm font-bold tabular-nums text-primary">
                        {bulat(data.energi_kkal)}
                    </span>
                    <span className="font-mono text-[11px] text-muted">kkal</span>

                    <span className="h-3 w-px bg-border" />

                    <span className="font-mono text-[11px] text-muted">
                        Protein{' '}
                        <b className="tabular-nums text-sage-dark">
                            {bulat(data.protein_g)}g
                        </b>
                    </span>
                </div>
            </div>
        </article>
    );
}
