import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';

const NUTRISI = [
    { key: 'energi_kkal', label: 'Energi', unit: 'kkal' },
    { key: 'protein_g', label: 'Protein', unit: 'g' },
    { key: 'lemak_g', label: 'Lemak', unit: 'g' },
    { key: 'karbohidrat_g', label: 'Karbohidrat', unit: 'g' },
];

const bulat = (nilai) => Math.round(Number(nilai) || 0);
const angka = (nilai) => (Number(nilai) || 0).toFixed(1);

/**
 * Panel nilai gizi per porsi, dengan dua mode.
 *
 *  - OTOMATIS (bawaan): nilai dihitung server dari daftar bahan. Panel
 *    menampilkan hasil hitungan sebagai bacaan, lengkap dengan seberapa
 *    besar cakupannya ("8 dari 10 bahan terhitung") dan daftar bahan yang
 *    tak bisa dihitung.
 *  - MANUAL: penulis mengisi empat kolom sendiri. Ini jalan keluar ketika
 *    ada bahan tak terhitung (mis. "secukupnya") yang membuat angka
 *    otomatis tidak bisa dipertanggungjawabkan penuh.
 */
export default function GiziPanel({
    data,
    setData,
    errors = {},
    porsi = 1,
    pratinjauGizi = null,
}) {
    const otomatis = data.gizi_mode !== 'manual';

    // Nilai yang ditampilkan: hasil hitungan bila otomatis, kiriman
    // penulis bila manual.
    const tampil = otomatis ? pratinjauGizi : data;

    const gantiMode = (mode) => {
        setData('gizi_mode', mode);

        // Kembali ke otomatis: isi kolom dengan hasil hitungan terkini,
        // supaya nilai yang tersimpan tidak menyimpang dari yang terlihat.
        if (mode === 'otomatis' && pratinjauGizi) {
            NUTRISI.forEach((n) => setData(n.key, pratinjauGizi[n.key]));
        }
    };

    return (
        <div>
            <div className="flex flex-wrap items-center justify-between gap-3">
                <p className="font-body text-sm text-muted">
                    {otomatis
                        ? 'Nilai gizi dihitung otomatis dari daftar bahan.'
                        : 'Anda mengisi nilai gizi sendiri (mode manual).'}
                </p>

                <button
                    type="button"
                    onClick={() => gantiMode(otomatis ? 'manual' : 'otomatis')}
                    className="rounded-lg border border-border bg-surface px-3 py-1.5 font-body text-xs font-semibold text-charcoal transition hover:bg-linen"
                >
                    {otomatis ? 'Isi manual' : 'Hitung otomatis'}
                </button>
            </div>

            {otomatis ? (
                // ── Mode otomatis: bacaan hasil hitungan ──
                <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {NUTRISI.map((n) => (
                        <div
                            key={n.key}
                            className="rounded-xl border border-border bg-warm-cream px-4 py-3"
                        >
                            <span className="font-mono text-[11px] font-semibold uppercase tracking-wider text-muted">
                                {n.label} ({n.unit})
                            </span>
                            <p className="mt-1 font-mono text-lg font-bold tabular-nums text-charcoal">
                                {n.key === 'energi_kkal'
                                    ? bulat(tampil?.[n.key])
                                    : angka(tampil?.[n.key])}
                            </p>
                        </div>
                    ))}
                </div>
            ) : (
                // ── Mode manual: input bebas ──
                <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {NUTRISI.map((n) => (
                        <div key={n.key}>
                            <InputLabel
                                value={`${n.label} (${n.unit})`}
                                htmlFor={`gizi-${n.key}`}
                            />
                            <TextInput
                                id={`gizi-${n.key}`}
                                type="number"
                                min="0"
                                step="0.1"
                                value={data[n.key]}
                                onChange={(e) => setData(n.key, e.target.value)}
                                className="mt-1 block w-full"
                            />
                            <InputError message={errors[n.key]} className="mt-1" />
                        </div>
                    ))}
                </div>
            )}

            {/* Cakupan perhitungan — transparan soal bahan mana yang
                tidak masuk hitungan. */}
            {otomatis && pratinjauGizi && (
                <div className="mt-4">
                    <div className="flex items-center justify-between gap-3">
                        <span className="font-body text-xs text-muted">
                            {pratinjauGizi.terhitung} dari {pratinjauGizi.totalBahan} bahan
                            terhitung
                        </span>
                        <span className="font-mono text-xs font-bold tabular-nums text-sage-dark">
                            {pratinjauGizi.cakupan}%
                        </span>
                    </div>

                    <div
                        className="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-border"
                        role="progressbar"
                        aria-valuenow={pratinjauGizi.cakupan}
                        aria-valuemin={0}
                        aria-valuemax={100}
                        aria-label="Cakupan perhitungan gizi"
                    >
                        <div
                            className={`h-full rounded-full transition-all ${
                                pratinjauGizi.cakupan === 100
                                    ? 'bg-mint-spark'
                                    : 'bg-terracotta'
                            }`}
                            style={{ width: `${pratinjauGizi.cakupan}%` }}
                        />
                    </div>

                    {pratinjauGizi.takTerhitung.length > 0 && (
                        <ul className="mt-2 space-y-1 rounded-xl bg-terracotta/5 px-3 py-2">
                            {pratinjauGizi.takTerhitung.map((b, i) => (
                                <li
                                    key={i}
                                    className="font-body text-xs text-terracotta-dark"
                                >
                                    <b>{b.nama}</b> — {b.alasan}
                                </li>
                            ))}
                        </ul>
                    )}

                    {pratinjauGizi.cakupan < 100 && (
                        <p className="mt-2 font-body text-xs text-muted">
                            Sebagian bahan tidak bisa dihitung otomatis. Angka gizi di atas
                            hanya mewakili bahan yang terhitung — gunakan mode manual bila
                            Anda punya angka yang lebih tepat.
                        </p>
                    )}
                </div>
            )}

            <div className="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 rounded-xl bg-mint-light px-4 py-3 ring-1 ring-inset ring-primary/10">
                <span className="font-mono text-[11px] font-semibold uppercase tracking-wider text-primary">
                    Per porsi
                </span>

                <span className="font-mono text-sm font-bold tabular-nums text-terracotta">
                    {bulat(tampil?.energi_kkal)} kkal
                </span>

                <span className="h-3 w-px bg-primary/20" />

                <span className="font-mono text-sm tabular-nums text-charcoal">
                    P <b className="text-sage-dark">{bulat(tampil?.protein_g)}</b>
                    <span className="text-muted">g</span> · L{' '}
                    <b>{bulat(tampil?.lemak_g)}</b>
                    <span className="text-muted">g</span> · K{' '}
                    <b>{bulat(tampil?.karbohidrat_g)}</b>
                    <span className="text-muted">g</span>
                </span>

                <span className="ms-auto font-body text-xs text-sage-dark">
                    untuk {porsi || 1} porsi
                </span>
            </div>
        </div>
    );
}
