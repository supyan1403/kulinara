import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { satuanDisarankan } from '@/utils/gizi';
import { useEffect, useMemo, useRef, useState } from 'react';

/**
 * Kolom pemilihan bahan dengan saran otomatis dari rujukan `bahan_pangan`.
 *
 * Penulis resep bebas mengetik nama apa pun (bahan custom). Tapi begitu
 * namanya mirip dengan rujukan, daftar saran muncul — dan memilih salah
 * satunya mengisi `bahan_id`, sekaligus menyesuaikan satuan dengan
 * satuan dasar bahan itu. Dengan `bahan_id` tersambung, nilai gizi baris
 * ini bisa dihitung otomatis oleh server.
 *
 * Sengaja memakai input teks biasa + daftar saran (bukan <datalist>),
 * karena perilaku <datalist> berbeda jauh antar peramban dan sering
 * gagal mengisi nilai saat dipilih lewat papan ketik.
 */
export default function PilihBahan({
    id,
    nilai = '',
    bahanId = null,
    bahanTersedia = [],
    onChange,
}) {
    const [terbuka, setTerbuka] = useState(false);
    const wadah = useRef(null);

    // Tutup daftar saran saat mengklik di luar kolom.
    useEffect(() => {
        if (!terbuka) return undefined;

        const tutup = (e) => {
            if (wadah.current && !wadah.current.contains(e.target)) {
                setTerbuka(false);
            }
        };

        document.addEventListener('mousedown', tutup);

        return () => document.removeEventListener('mousedown', tutup);
    }, [terbuka]);

    const saran = useMemo(() => {
        const kata = nilai.trim().toLowerCase();

        if (!kata) return [];

        return bahanTersedia
            .filter((b) => b.nama.toLowerCase().includes(kata))
            .slice(0, 6);
    }, [nilai, bahanTersedia]);

    const pilih = (bahan) => {
        onChange({
            nama: bahan.nama,
            bahanId: bahan.id,
            satuan: satuanDisarankan(bahan),
        });

        setTerbuka(false);
    };

    return (
        <div ref={wadah} className="relative">
            <InputLabel value="Nama bahan" htmlFor={id} />

            <TextInput
                id={id}
                value={nilai}
                onChange={(e) => {
                    // Mengetik manual memutus kaitan ke rujukan — baris ini
                    // kembali jadi bahan custom sampai saran dipilih lagi.
                    onChange({ nama: e.target.value, bahanId: null });
                    setTerbuka(true);
                }}
                onFocus={() => setTerbuka(true)}
                placeholder="Ketik atau pilih dari rujukan bahan"
                role="combobox"
                aria-expanded={terbuka && saran.length > 0}
                aria-controls={`${id}-saran`}
                aria-autocomplete="list"
                autoComplete="off"
                className="mt-1 block w-full"
            />

            {bahanId != null && (
                <p className="mt-1 font-mono text-[11px] text-sage-dark">
                    tersambung ke rujukan bahan
                </p>
            )}

            {terbuka && saran.length > 0 && (
                <ul
                    id={`${id}-saran`}
                    role="listbox"
                    className="absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-lg border border-border bg-surface py-1 shadow-overlay"
                >
                    {saran.map((b) => (
                        <li key={b.id} role="option" aria-selected={b.id === bahanId}>
                            <button
                                type="button"
                                onClick={() => pilih(b)}
                                className={`flex w-full items-baseline justify-between gap-3 px-3 py-2 text-left text-sm transition hover:bg-linen ${
                                    b.id === bahanId ? 'bg-mint-light text-primary' : 'text-charcoal'
                                }`}
                            >
                                <span className="truncate">{b.nama}</span>
                                <span className="shrink-0 font-mono text-[11px] text-muted">
                                    {b.kode}
                                </span>
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
