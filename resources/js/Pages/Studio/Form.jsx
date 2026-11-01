import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import { IconTrash } from '@/Components/Icons';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { STATUS_LABELS } from '@/Components/StatusBadge';
import TextInput from '@/Components/TextInput';
import BahanList from '@/Components/Studio/BahanList';
import GiziPanel from '@/Components/Studio/GiziPanel';
import LangkahList from '@/Components/Studio/LangkahList';
import ResepPreview from '@/Components/Studio/ResepPreview';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { hitungGizi } from '@/utils/gizi';
import { urlGambar } from '@/utils/gambar';
import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

const BARIS_BAHAN_KOSONG = { nama: '', jumlah: '', satuan: 'gram', bahan_id: null };

/**
 * Satu halaman formulir untuk mode buat dan ubah (`resep` null = buat baru).
 *
 * Catatan: `gambar` di form selalu berisi berkas BARU saja. Path gambar lama
 * tidak ikut dikirim agar tidak divalidasi sebagai berkas.
 */
export default function StudioForm({
    resep,
    kategoriTersedia,
    kesulitanTersedia,
    statusDiizinkan,
    bahanTersedia = [],
}) {
    const { data, setData, post, put, processing, errors } = useForm({
        judul: resep?.judul ?? '',
        deskripsi: resep?.deskripsi ?? '',
        kategori: resep?.kategori ?? '',
        tingkat_kesulitan: resep?.tingkat_kesulitan ?? 'sedang',
        waktu_masak_menit: resep?.waktu_masak_menit ?? 30,
        porsi: resep?.porsi ?? 1,
        // Mode gizi bawaan otomatis. Untuk resep lama, server mengirim
        // `gizi_mode` sesuai tersimpan di kolom — default `manual` untuk
        // resep yang gizinya memang diisi tangan sebelum fitur ini ada.
        gizi_mode: resep?.gizi_mode ?? 'otomatis',
        energi_kkal: resep?.energi_kkal ?? '',
        protein_g: resep?.protein_g ?? '',
        lemak_g: resep?.lemak_g ?? '',
        karbohidrat_g: resep?.karbohidrat_g ?? '',
        // Standarisasi — hasil akhir & peralatan. Opsional; resep lama
        // dibiarkan kosong.
        hasil_total: resep?.hasil_total ?? '',
        satuan_hasil: resep?.satuan_hasil ?? 'gram',
        alat: Array.isArray(resep?.alat) && resep.alat.length ? resep.alat : [''],
        bahan: resep?.bahan?.length ? resep.bahan : [{ ...BARIS_BAHAN_KOSONG }],
        langkah: resep?.langkah?.length ? resep.langkah : [''],
        status: resep?.status ?? 'draft',
        gambar: null,
    });

    // Pratinjau gizi yang dihitung di klien — hanya untuk menghidupkan
    // panel saat mengetik. Nilai yang benar-benar tersimpan selalu
    // dihitung ulang oleh server saat disimpan (lihat utils/gizi.js).
    const pratinjauGizi = useMemo(
        () => hitungGizi(data.bahan, data.porsi, bahanTersedia),
        [data.bahan, data.porsi, bahanTersedia],
    );

    // Pratinjau berkas yang baru dipilih — dibuat saat dipilih, dibersihkan
    // saat diganti atau saat halaman ditinggalkan agar URL tidak bocor.
    const [pratinjauBaru, setPratinjauBaru] = useState(null);

    useEffect(() => {
        if (!data.gambar) {
            setPratinjauBaru(null);

            return undefined;
        }

        const url = URL.createObjectURL(data.gambar);
        setPratinjauBaru(url);

        return () => URL.revokeObjectURL(url);
    }, [data.gambar]);

    const ubah = (kunci, nilai) => setData(kunci, nilai);

    const kirim = (e) => {
        e.preventDefault();

        const opsi = { preserveScroll: true };

        if (resep) {
            put(route('studio.update', resep.id), opsi);
        } else {
            post(route('studio.store'), opsi);
        }
    };

    const kembali = () => router.visit(route('studio.index'));

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-bold leading-tight tracking-tight text-charcoal">
                            {resep ? 'Ubah Resep' : 'Buat Resep Baru'}
                        </h2>
                        <p className="mt-1 font-body text-sm text-muted">
                            {resep
                                ? 'Sunting isi resep lalu simpan perubahan Anda.'
                                : 'Isi identitas, susun bahan dan langkah, lalu tinjau kartunya.'}
                        </p>
                    </div>

                    <SecondaryButton type="button" onClick={kembali}>
                        Kembali
                    </SecondaryButton>
                </div>
            }
        >
            <Head title={resep ? 'Ubah Resep' : 'Buat Resep'} />

            <form onSubmit={kirim} className="py-12">
                <div className="mx-auto max-w-shell sm:px-6 lg:px-8">
                    <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem] xl:items-start">
                        <div className="space-y-6">
                            {/* 01 — Identitas */}
                            <Seksy
                                nomor="01"
                                judul="Identitas resep"
                                deskripsi="Informasi dasar yang tampil di kartu resep."
                            >
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="sm:col-span-2">
                                        <InputLabel value="Judul resep" htmlFor="judul" />
                                        <TextInput
                                            id="judul"
                                            isFocused
                                            value={data.judul}
                                            onChange={(e) => ubah('judul', e.target.value)}
                                            placeholder="Contoh: Sayur Lodeh Terong Muda"
                                            className="mt-1 block w-full"
                                        />
                                        <InputError message={errors.judul} className="mt-1" />
                                    </div>

                                    <div className="sm:col-span-2">
                                        <InputLabel
                                            value="Deskripsi"
                                            htmlFor="deskripsi"
                                        />
                                        <textarea
                                            id="deskripsi"
                                            rows={3}
                                            value={data.deskripsi}
                                            onChange={(e) =>
                                                ubah('deskripsi', e.target.value)
                                            }
                                            placeholder="Gambaran singkat rasa dan ciri khas resep ini…"
                                            className="mt-1 block w-full rounded-lg border-border bg-surface text-sm text-charcoal placeholder:text-muted shadow-sm focus:border-primary focus:ring-primary focus:ring-offset-warm-cream"
                                        />
                                        <InputError
                                            message={errors.deskripsi}
                                            className="mt-1"
                                        />
                                    </div>

                                    <div>
                                        <InputLabel value="Kategori" htmlFor="kategori" />
                                        <select
                                            id="kategori"
                                            value={data.kategori}
                                            onChange={(e) => ubah('kategori', e.target.value)}
                                            className="mt-1 block w-full rounded-lg border-border bg-surface text-sm text-charcoal shadow-sm focus:border-primary focus:ring-primary focus:ring-offset-warm-cream"
                                        >
                                            <option value="">Pilih kategori</option>
                                            {kategoriTersedia.map((k) => (
                                                <option key={k.value} value={k.value}>
                                                    {k.label}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError
                                            message={errors.kategori}
                                            className="mt-1"
                                        />
                                    </div>

                                    <div>
                                        <InputLabel
                                            value="Tingkat kesulitan"
                                            htmlFor="kesulitan"
                                        />
                                        <select
                                            id="kesulitan"
                                            value={data.tingkat_kesulitan}
                                            onChange={(e) =>
                                                ubah('tingkat_kesulitan', e.target.value)
                                            }
                                            className="mt-1 block w-full rounded-lg border-border bg-surface text-sm text-charcoal shadow-sm focus:border-primary focus:ring-primary focus:ring-offset-warm-cream"
                                        >
                                            {kesulitanTersedia.map((k) => (
                                                <option key={k.value} value={k.value}>
                                                    {k.label}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError
                                            message={errors.tingkat_kesulitan}
                                            className="mt-1"
                                        />
                                    </div>

                                    <div>
                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <InputLabel
                                                    value="Waktu (menit)"
                                                    htmlFor="waktu"
                                                />
                                                <TextInput
                                                    id="waktu"
                                                    type="number"
                                                    min="1"
                                                    max="9999"
                                                    value={data.waktu_masak_menit}
                                                    onChange={(e) =>
                                                        ubah(
                                                            'waktu_masak_menit',
                                                            e.target.value,
                                                        )
                                                    }
                                                    className="mt-1 block w-full"
                                                />
                                                <InputError
                                                    message={errors.waktu_masak_menit}
                                                    className="mt-1"
                                                />
                                            </div>

                                            <div>
                                                <InputLabel value="Porsi" htmlFor="porsi" />
                                                <TextInput
                                                    id="porsi"
                                                    type="number"
                                                    min="1"
                                                    max="100"
                                                    value={data.porsi}
                                                    onChange={(e) =>
                                                        ubah('porsi', e.target.value)
                                                    }
                                                    className="mt-1 block w-full"
                                                />
                                                <InputError
                                                    message={errors.porsi}
                                                    className="mt-1"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </Seksy>

                            {/* 02 — Bahan */}
                            <Seksy
                                nomor="02"
                                judul="Bahan"
                                deskripsi="Satu baris per bahan — nama, jumlah, dan satuan."
                            >
                                <BahanList
                                    value={data.bahan}
                                    onChange={(nilai) => ubah('bahan', nilai)}
                                    errors={errors}
                                    bahanTersedia={bahanTersedia}
                                />
                            </Seksy>

                            {/* 03 — Langkah */}
                            <Seksy
                                nomor="03"
                                judul="Langkah memasak"
                                deskripsi="Susun urutannya dengan tombol naik/turun."
                            >
                                <LangkahList
                                    value={data.langkah}
                                    onChange={(nilai) => ubah('langkah', nilai)}
                                    errors={errors}
                                />
                            </Seksy>

                            {/* 04 — Gizi, gambar, terbitkan */}
                            <Seksy
                                nomor="04"
                                judul="Gizi, gambar & terbitkan"
                                deskripsi="Nilai gizi dihitung per porsi."
                            >
                                <GiziPanel
                                    data={data}
                                    setData={ubah}
                                    errors={errors}
                                    porsi={data.porsi}
                                    pratinjauGizi={pratinjauGizi}
                                />

                                {/* Standarisasi — hasil akhir & peralatan.
                                    Semua opsional: mengisi keduanya membuat
                                    resep memenuhi syarat badge "Standar"
                                    (bila gizi otomatis penuh), yang
                                    memungkinkan rendemen & skala produksi
                                    dihitung. */}
                                <div className="mt-6 rounded-xl border border-border bg-warm-cream p-4">
                                    <p className="font-body text-sm font-semibold text-charcoal">
                                        Standar & hasil
                                    </p>
                                    <p className="mt-1 font-body text-xs text-muted">
                                        Opsional — mengisi ini membuat resep memenuhi
                                        syarat badge <b>Standar</b> (gizi otomatis penuh),
                                        sehingga rendemen & skala produksi bisa dihitung.
                                    </p>

                                    <div className="mt-4 grid gap-4 sm:grid-cols-[minmax(0,1fr)_10rem]">
                                        <div>
                                            <InputLabel
                                                value="Hasil akhir (opsional)"
                                                htmlFor="hasil_total"
                                            />
                                            <TextInput
                                                id="hasil_total"
                                                type="number"
                                                min="0.01"
                                                step="0.01"
                                                value={data.hasil_total}
                                                onChange={(e) =>
                                                    ubah('hasil_total', e.target.value)
                                                }
                                                placeholder="Contoh: 900"
                                                className="mt-1 block w-full"
                                            />
                                            <InputError
                                                message={errors.hasil_total}
                                                className="mt-1"
                                            />
                                        </div>

                                        <div>
                                            <InputLabel
                                                value="Satuan hasil"
                                                htmlFor="satuan_hasil"
                                            />
                                            <select
                                                id="satuan_hasil"
                                                value={data.satuan_hasil}
                                                onChange={(e) =>
                                                    ubah('satuan_hasil', e.target.value)
                                                }
                                                className="mt-1 block w-full rounded-lg border-border bg-surface text-sm text-charcoal shadow-sm focus:border-primary focus:ring-primary focus:ring-offset-warm-cream"
                                            >
                                                <option value="gram">gram</option>
                                                <option value="ml">mililiter</option>
                                            </select>
                                            <InputError
                                                message={errors.satuan_hasil}
                                                className="mt-1"
                                            />
                                        </div>
                                    </div>

                                    {/* Daftar alat dinamis */}
                                    <div className="mt-4">
                                        <InputLabel value="Peralatan (opsional)" />

                                        <div className="mt-2 space-y-2">
                                            {data.alat.map((nama, i) => (
                                                <div key={i} className="flex items-center gap-2">
                                                    <TextInput
                                                        value={nama}
                                                        onChange={(e) =>
                                                            ubah(
                                                                'alat',
                                                                data.alat.map((a, idx) =>
                                                                    idx === i
                                                                        ? e.target.value
                                                                        : a,
                                                                ),
                                                            )
                                                        }
                                                        placeholder="Contoh: blender, wajan, panci"
                                                        className="block w-full"
                                                        aria-label={`Peralatan ${i + 1}`}
                                                    />
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            ubah(
                                                                'alat',
                                                                data.alat.filter(
                                                                    (_, idx) => idx !== i,
                                                                ),
                                                            )
                                                        }
                                                        disabled={data.alat.length <= 1}
                                                        aria-label={`Hapus peralatan ${i + 1}`}
                                                        className="shrink-0 rounded-lg p-1.5 text-muted transition hover:bg-danger/10 hover:text-danger focus:outline-none focus-visible:ring-2 focus-visible:ring-danger disabled:cursor-not-allowed disabled:opacity-40"
                                                    >
                                                        <IconTrash className="h-4 w-4" />
                                                    </button>
                                                </div>
                                            ))}
                                        </div>

                                        <button
                                            type="button"
                                            onClick={() => ubah('alat', [...data.alat, ''])}
                                            className="mt-2 font-body text-xs font-semibold text-primary transition hover:text-primary-dark"
                                        >
                                            + Tambah peralatan
                                        </button>

                                        <InputError message={errors.alat} className="mt-1" />
                                        {Object.keys(errors)
                                            .filter((k) => k.startsWith('alat.'))
                                            .map((k) => (
                                                <InputError
                                                    key={k}
                                                    message={errors[k]}
                                                    className="mt-1"
                                                />
                                            ))}
                                    </div>
                                </div>

                                <div className="mt-6">
                                    <InputLabel value="Gambar resep" htmlFor="gambar" />
                                    <input
                                        id="gambar"
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        onChange={(e) =>
                                            ubah('gambar', e.target.files?.[0] ?? null)
                                        }
                                        className="mt-1 block w-full text-sm text-muted file:mr-3 file:rounded-lg file:border-0 file:bg-linen file:px-4 file:py-2 file:text-xs file:font-semibold file:uppercase file:tracking-widest file:text-charcoal hover:file:bg-border"
                                    />
                                    <p className="mt-1 font-body text-xs text-muted">
                                        JPG, PNG, atau WebP; maksimal 2 MB.
                                        {resep?.gambar &&
                                            ' Kosongkan untuk mempertahankan gambar saat ini.'}
                                    </p>
                                    <InputError message={errors.gambar} className="mt-1" />
                                </div>

                                {statusDiizinkan.length > 1 ? (
                                    <fieldset className="mt-6">
                                        <legend className="text-sm font-medium text-charcoal">
                                            Status publikasi
                                        </legend>

                                        <div className="mt-2 flex flex-wrap gap-2">
                                            {statusDiizinkan.map((s) => (
                                                <label
                                                    key={s}
                                                    className={`cursor-pointer rounded-full px-4 py-2 text-sm font-semibold ring-1 ring-inset transition ${
                                                        data.status === s
                                                            ? 'bg-primary text-white ring-primary'
                                                            : 'bg-surface text-charcoal ring-border hover:bg-linen'
                                                    }`}
                                                >
                                                    <input
                                                        type="radio"
                                                        name="status"
                                                        value={s}
                                                        checked={data.status === s}
                                                        onChange={(e) =>
                                                            ubah('status', e.target.value)
                                                        }
                                                        className="sr-only"
                                                    />
                                                    {STATUS_LABELS[s] ?? s}
                                                </label>
                                            ))}
                                        </div>

                                        <InputError message={errors.status} className="mt-2" />
                                    </fieldset>
                                ) : (
                                    // Kreator tidak punya pilihan status: resep
                                    // baru selalu draft, lalu diajukan lewat
                                    // tombol "Ajukan" di Studio. Menampilkan
                                    // satu-satunya pilihan sebagai radio hanya
                                    // menyesatkan.
                                    <div className="mt-6 rounded-xl border border-border bg-warm-cream px-4 py-3">
                                        <p className="font-body text-sm font-semibold text-charcoal">
                                            Sebelum tayang
                                        </p>
                                        <p className="mt-1 font-body text-xs leading-relaxed text-muted">
                                            Resep tersimpan sebagai draft. Setelah
                                            selesai, ajukan dari Studio Dapur — Pengelola
                                            akan meninjaunya sebelum ditampilkan ke
                                            pengunjung.
                                        </p>
                                    </div>
                                )}

                                <div className="mt-6 flex flex-wrap items-center gap-3 border-t border-border pt-6">
                                    <PrimaryButton type="submit" disabled={processing}>
                                        {processing
                                            ? 'Menyimpan…'
                                            : resep
                                              ? 'Simpan Perubahan'
                                              : 'Simpan Resep'}
                                    </PrimaryButton>

                                    <SecondaryButton
                                        type="button"
                                        disabled={processing}
                                        onClick={kembali}
                                    >
                                        Batal
                                    </SecondaryButton>
                                </div>
                            </Seksy>
                        </div>

                        {/* Pratinjau kartu */}
                        <aside className="xl:sticky xl:top-24">
                            <div className="mb-3 flex items-baseline justify-between gap-3">
                                <h3 className="text-sm font-bold text-charcoal">
                                    Pratinjau kartu
                                </h3>
                                <span className="font-mono text-[11px] uppercase tracking-wider text-muted">
                                    seperti penikmat
                                </span>
                            </div>

                            <ResepPreview
                                data={data}
                                src={pratinjauBaru ?? urlGambar(resep?.gambar)}
                                kategoriTersedia={kategoriTersedia}
                            />

                            {(pratinjauBaru || resep?.gambar) && (
                                <p className="mt-2 truncate font-mono text-[11px] text-muted">
                                    {pratinjauBaru
                                        ? 'gambar baru belum tersimpan'
                                        : resep.gambar}
                                </p>
                            )}
                        </aside>
                    </div>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}

/** Kartu seksi bernomor — konsisten dengan gaya penanda di halaman utama. */
function Seksy({ nomor, judul, deskripsi, children }) {
    return (
        <section className="border border-border bg-surface shadow-card sm:rounded-2xl">
            <div className="border-b border-border bg-linen/60 px-6 py-4">
                <div className="flex items-baseline gap-3">
                    <span className="font-mono text-xs font-semibold uppercase tracking-wider text-primary">
                        {nomor}
                    </span>
                    <h3 className="text-base font-bold text-charcoal">{judul}</h3>
                </div>
                {deskripsi && (
                    <p className="mt-1 font-body text-sm text-muted">{deskripsi}</p>
                )}
            </div>

            <div className="p-6">{children}</div>
        </section>
    );
}
