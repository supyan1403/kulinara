import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import { IconPlus, IconTrash } from '@/Components/Icons';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import PilihBahan from '@/Components/Studio/PilihBahan';

const SATUAN = [
    'gram',
    'kg',
    'ml',
    'liter',
    'buah',
    'siung',
    'ikat',
    'lembar',
    'sdt',
    'sdm',
    'ruas',
    'butir',
    'genggam',
    'secukupnya',
];

const BARIS_KOSONG = { nama: '', jumlah: '', satuan: 'gram', bahan_id: null };

/**
 * Daftar bahan dinamis — tiap entri berbentuk {nama, jumlah, satuan} dan,
 * bila dipilih dari rujukan, `bahan_id` yang menunjuk `bahan_pangan`.
 *
 * `bahan_id` adalah jembatan gizi otomatis: baris yang tersambung ke
 * rujukan bisa dihitung nilainya oleh server, sementara baris tanpa id
 * tetap sah sebagai bahan custom (hanya gizinya tidak terhitung).
 */
export default function BahanList({ value = [], onChange, errors = {}, bahanTersedia = [] }) {
    const ubahBaris = (i, tambal) =>
        onChange(value.map((bahan, idx) => (idx === i ? { ...bahan, ...tambal } : bahan)));

    const tambah = () => onChange([...value, { ...BARIS_KOSONG }]);
    const hapus = (i) => onChange(value.filter((_, idx) => idx !== i));

    return (
        <div className="space-y-3">
            {value.map((bahan, i) => (
                <div key={i} className="rounded-xl border border-border bg-warm-cream p-3">
                    <div className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_6rem_9rem]">
                        <PilihBahan
                            id={`bahan-nama-${i}`}
                            nilai={bahan.nama}
                            bahanId={bahan.bahan_id}
                            bahanTersedia={bahanTersedia}
                            onChange={(tambal) => ubahBaris(i, tambal)}
                        />

                        <div>
                            <InputLabel value="Jumlah" htmlFor={`bahan-jumlah-${i}`} />
                            <TextInput
                                id={`bahan-jumlah-${i}`}
                                type="number"
                                min="0"
                                step="0.01"
                                value={bahan.jumlah}
                                onChange={(e) => ubahBaris(i, { jumlah: e.target.value })}
                                className="mt-1 block w-full"
                            />
                        </div>

                        <div>
                            <InputLabel value="Satuan" htmlFor={`bahan-satuan-${i}`} />
                            <select
                                id={`bahan-satuan-${i}`}
                                value={bahan.satuan}
                                onChange={(e) => ubahBaris(i, { satuan: e.target.value })}
                                className="mt-1 block w-full rounded-lg border-border bg-surface text-sm text-charcoal shadow-sm focus:border-primary focus:ring-primary focus:ring-offset-warm-cream"
                            >
                                {SATUAN.map((s) => (
                                    <option key={s} value={s}>
                                        {s}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>

                    <div className="mt-2 flex items-start justify-between gap-3">
                        <div className="space-y-1">
                            <InputError message={errors[`bahan.${i}.nama`]} />
                            <InputError message={errors[`bahan.${i}.jumlah`]} />
                            <InputError message={errors[`bahan.${i}.satuan`]} />
                        </div>

                        <button
                            type="button"
                            onClick={() => hapus(i)}
                            disabled={value.length <= 1}
                            aria-label={`Hapus bahan ${i + 1}`}
                            className="shrink-0 rounded-lg p-1.5 text-muted transition hover:bg-danger/10 hover:text-danger focus:outline-none focus-visible:ring-2 focus-visible:ring-danger disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            <IconTrash className="h-4 w-4" />
                        </button>
                    </div>
                </div>
            ))}

            <div className="flex flex-wrap items-center justify-between gap-3">
                <SecondaryButton type="button" onClick={tambah}>
                    <span className="inline-flex items-center gap-1.5">
                        <IconPlus className="h-4 w-4" />
                        Tambah bahan
                    </span>
                </SecondaryButton>

                <InputError message={errors.bahan} />
            </div>
        </div>
    );
}
