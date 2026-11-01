import InputError from '@/Components/InputError';
import { IconArrowDown, IconArrowUp, IconPlus, IconTrash } from '@/Components/Icons';
import SecondaryButton from '@/Components/SecondaryButton';

/**
 * Daftar langkah memasak dinamis — tiap entri berupa satu string,
 * persis seperti isi kolom jsonb `langkah`.
 *
 * Baris bisa disusun ulang lewat tombol naik/turun tanpa drag & drop.
 */
export default function LangkahList({ value = [], onChange, errors = {} }) {
    const ubah = (i, isi) =>
        onChange(value.map((teks, idx) => (idx === i ? isi : teks)));

    const tambah = () => onChange([...value, '']);
    const hapus = (i) => onChange(value.filter((_, idx) => idx !== i));

    const geser = (i, arah) => {
        const tujuan = i + arah;
        if (tujuan < 0 || tujuan >= value.length) return;

        const baru = [...value];
        [baru[i], baru[tujuan]] = [baru[tujuan], baru[i]];
        onChange(baru);
    };

    return (
        <div className="space-y-3">
            {value.map((langkah, i) => (
                <div key={i} className="rounded-xl border border-border bg-warm-cream p-3">
                    <div className="flex gap-3">
                        <span className="mt-6 hidden h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary font-mono text-xs font-bold text-white sm:inline-flex">
                            {i + 1}
                        </span>

                        <div className="min-w-0 flex-1">
                            <label
                                htmlFor={`langkah-${i}`}
                                className="mb-1 block text-sm font-medium text-charcoal sm:sr-only"
                            >
                                Langkah {i + 1}
                            </label>

                            <textarea
                                id={`langkah-${i}`}
                                rows={2}
                                value={langkah}
                                onChange={(e) => ubah(i, e.target.value)}
                                placeholder="Contoh: Tumis bumbu halus sampai harum."
                                className="block w-full rounded-lg border-border bg-surface text-sm text-charcoal placeholder:text-muted shadow-sm focus:border-primary focus:ring-primary focus:ring-offset-warm-cream"
                            />
                        </div>

                        <div className="flex shrink-0 flex-col gap-1 sm:flex-row sm:items-start sm:pt-6">
                            <button
                                type="button"
                                onClick={() => geser(i, -1)}
                                disabled={i === 0}
                                aria-label={`Naikkan langkah ${i + 1}`}
                                className="rounded-lg border border-border bg-surface p-1.5 text-muted transition hover:bg-linen hover:text-charcoal focus:outline-none focus:ring-2 focus:ring-primary disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                <IconArrowUp className="h-4 w-4" />
                            </button>

                            <button
                                type="button"
                                onClick={() => geser(i, 1)}
                                disabled={i === value.length - 1}
                                aria-label={`Turunkan langkah ${i + 1}`}
                                className="rounded-lg border border-border bg-surface p-1.5 text-muted transition hover:bg-linen hover:text-charcoal focus:outline-none focus:ring-2 focus:ring-primary disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                <IconArrowDown className="h-4 w-4" />
                            </button>

                            <button
                                type="button"
                                onClick={() => hapus(i)}
                                disabled={value.length <= 1}
                                aria-label={`Hapus langkah ${i + 1}`}
                                className="rounded-lg border border-border bg-surface p-1.5 text-muted transition hover:bg-danger/10 hover:text-danger focus:outline-none focus:ring-2 focus:ring-danger disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                <IconTrash className="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div className="mt-2 sm:ps-10">
                        <InputError message={errors[`langkah.${i}`]} />
                    </div>
                </div>
            ))}

            <div className="flex flex-wrap items-center justify-between gap-3">
                <SecondaryButton type="button" onClick={tambah}>
                    <span className="inline-flex items-center gap-1.5">
                        <IconPlus className="h-4 w-4" />
                        Tambah langkah
                    </span>
                </SecondaryButton>

                <InputError message={errors.langkah} />
            </div>
        </div>
    );
}
