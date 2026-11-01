import AvatarInisial from '@/Components/AvatarInisial';
import BintangRating from '@/Components/BintangRating';
import PilihBintang from '@/Components/PilihBintang';
import { IconChat, IconPencil, IconTrash } from '@/Components/Icons';
import { Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

/**
 * Bagian ulasan & rating di bawah langkah memasak.
 *
 * Aturan yang tercermin di tata letaknya:
 *  - Pengunjung yang belum masuk melihat daftar ulasan, tapi tidak formulir.
 *    Yang tampil adalah ajakan masuk — tanpa itu, satu-satunya reaksi yang
 *    mungkin adalah pergi.
 *  - Penulis resep melihat kotak balasan di bawah setiap ulasan, tapi tidak
 *    melihat formulir tulis. Mengulas karya sendiri tidak diizinkan server,
 *    jadi menampilkan formulirnya hanya akan berujung penolakan.
 *  - Satu pengguna hanya punya satu ulasan. Kalau sudah ada, formulirnya
 *    berubah jadi mode ubah, bukan memunculkan baris kedua.
 */
export default function BagianUlasan({
    resepId,
    ulasan = [],
    ulasanSaya = null,
    bolehBalas = false,
    masuk = false,
    rataRating = 0,
    jumlahUlasan = 0,
}) {
    return (
        <section aria-labelledby="judul-ulasan" className="mt-16 border-t border-border pt-10">
            <div className="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 id="judul-ulasan" className="text-2xl font-extrabold tracking-tight">
                        Ulasan &amp; Rating
                    </h2>

                    {jumlahUlasan > 0 ? (
                        <div className="mt-2 flex items-center gap-3">
                            <BintangRating nilai={rataRating} ukuran="md" />
                            <span className="font-body text-sm text-muted">
                                <strong className="font-semibold text-charcoal">
                                    {Number(rataRating).toFixed(1)}
                                </strong>{' '}
                                dari {jumlahUlasan} ulasan
                            </span>
                        </div>
                    ) : (
                        <p className="mt-2 font-body text-sm text-muted">
                            Belum ada yang mengulas resep ini.
                        </p>
                    )}
                </div>

                {/* Jumlah ulasan ditampilkan di kepala bagian supaya pengunjung
                    tahu apa yang menantinya sebelum menggulir — daftar kosong
                    yang panjang membuat halaman terasa belum selesai. */}
                {jumlahUlasan > 0 && (
                    <span className="inline-flex items-center gap-1.5 rounded-full border border-border bg-surface px-3 py-1 text-xs font-semibold text-muted">
                        <IconChat className="h-3.5 w-3.5" />
                        {jumlahUlasan} ulasan
                    </span>
                )}
            </div>

            <div className="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
                {/* Di desktop formulir di kolom kanan (sticky). Di ponsel
                    formulir ditaruh setelah daftar ulasan agar pembaca
                    melihat ulasan yang ada terlebih dahulu. */}
                <div className="order-1 lg:order-1 space-y-4">
                    {ulasan.length === 0 ? (
                        <p className="rounded-2xl border border-dashed border-border bg-surface px-6 py-10 text-center font-body text-sm text-muted">
                            {masuk
                                ? 'Jadilah yang pertama memberi penilaian untuk resep ini.'
                                : 'Masuk untuk menjadi yang pertama memberi penilaian.'}
                        </p>
                    ) : (
                        ulasan.map((item) => (
                            <KartuUlasan
                                key={item.id}
                                item={item}
                                resepId={resepId}
                                bolehBalas={bolehBalas}
                                milikSaya={ulasanSaya?.id === item.id}
                            />
                        ))
                    )}
                </div>

                <div className="order-2 lg:order-2 lg:sticky lg:top-24 lg:self-start">
                    <FormulirUlasan
                        resepId={resepId}
                        ulasanSaya={ulasanSaya}
                        masuk={masuk}
                        penulisResep={bolehBalas}
                    />
                </div>
            </div>
        </section>
    );
}

/**
 * Satu kartu ulasan beserta balasan pemilik resep.
 */
function KartuUlasan({ item, resepId, bolehBalas, milikSaya }) {
    const [bukaBalasan, setBukaBalasan] = useState(false);

    const hapus = () => {
        if (!window.confirm('Hapus ulasan ini?')) return;

        router.delete(route('ulasan.destroy', item.id), { preserveScroll: true });
    };

    return (
        <article className="rounded-2xl border border-border bg-surface p-5 shadow-card">
            <div className="flex items-start gap-3">
                <AvatarInisial nama={item.penulis} foto={item.avatar_path} ukuran="md" />

                <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                        <p className="truncate font-display text-sm font-semibold text-charcoal">
                            {item.penulis}
                        </p>
                        <BintangRating nilai={item.rating} />
                        {item.dibuat && (
                            <span className="font-body text-xs text-muted">{item.dibuat}</span>
                        )}
                    </div>

                    {item.komentar ? (
                        <p className="mt-2 whitespace-pre-line font-body text-sm leading-relaxed text-charcoal">
                            {item.komentar}
                        </p>
                    ) : (
                        <p className="mt-2 font-body text-sm italic text-muted">
                            Hanya memberi bintang, tanpa komentar.
                        </p>
                    )}
                </div>

                {/* Aksi hanya muncul untuk yang berhak. `milikSaya` menentukan
                    tombol ubah/hapus; `bolehBalas` menentukan tombol balas. */}
                <div className="flex shrink-0 items-center gap-1">
                    {milikSaya && (
                        <button
                            type="button"
                            onClick={hapus}
                            aria-label="Hapus ulasan saya"
                            title="Hapus ulasan"
                            className="rounded-lg p-1.5 text-muted transition hover:bg-terracotta/10 hover:text-terracotta focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                        >
                            <IconTrash className="h-4 w-4" />
                        </button>
                    )}

                    {bolehBalas && !item.balasan && (
                        <button
                            type="button"
                            onClick={() => setBukaBalasan((v) => !v)}
                            className="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-primary transition hover:bg-mint-light focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                        >
                            Balas
                        </button>
                    )}

                    {bolehBalas && item.balasan && (
                        <button
                            type="button"
                            onClick={() => setBukaBalasan((v) => !v)}
                            aria-label="Ubah balasan"
                            title="Ubah balasan"
                            className="rounded-lg p-1.5 text-muted transition hover:bg-linen hover:text-charcoal focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                        >
                            <IconPencil className="h-4 w-4" />
                        </button>
                    )}
                </div>
            </div>

            {/* Balasan pemilik resep — menjorok dan berlatar hijau muda supaya
                jelas ini suara penulis resep, bukan ulasan kedua. */}
            {item.balasan && !bukaBalasan && (
                <div className="mt-4 ms-11 rounded-xl border-s-4 border-primary bg-mint-light/60 px-4 py-3">
                    <p className="font-display text-xs font-semibold text-primary">
                        Balasan penulis resep
                        {item.balasan_at && (
                            <span className="ms-2 font-body font-normal text-muted">
                                {item.balasan_at}
                            </span>
                        )}
                    </p>
                    <p className="mt-1.5 whitespace-pre-line font-body text-sm leading-relaxed text-charcoal">
                        {item.balasan}
                    </p>
                </div>
            )}

            {bolehBalas && bukaBalasan && (
                <FormulirBalasan
                    ulasanId={item.id}
                    balasanAwal={item.balasan ?? ''}
                    onSelesai={() => setBukaBalasan(false)}
                />
            )}
        </article>
    );
}

/**
 * Formulir balasan pemilik resep.
 *
 * Mengirim isi kosong berarti menghapus balasan yang ada — itu memberi pemilik
 * resep cara membatalkan balasannya tanpa tombol terpisah.
 */
function FormulirBalasan({ ulasanId, balasanAwal, onSelesai }) {
    const { data, setData, post, processing, errors } = useForm({
        balasan: balasanAwal,
    });

    const kirim = (e) => {
        e.preventDefault();

        post(route('ulasan.balas', ulasanId), {
            preserveScroll: true,
            onSuccess: () => onSelesai?.(),
        });
    };

    return (
        <form onSubmit={kirim} className="mt-4 ms-11 space-y-2">
            <label htmlFor={`balasan-${ulasanId}`} className="sr-only">
                Balasan untuk ulasan ini
            </label>

            <textarea
                id={`balasan-${ulasanId}`}
                value={data.balasan}
                onChange={(e) => setData('balasan', e.target.value)}
                rows={3}
                placeholder="Tulis balasan… kosongkan untuk menghapus balasan."
                className="w-full rounded-xl border border-border bg-warm-cream px-3.5 py-2.5 font-body text-sm text-charcoal shadow-sm transition placeholder:text-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
            />

            {errors.balasan && (
                <p className="font-body text-xs text-danger">{errors.balasan}</p>
            )}

            <div className="flex items-center gap-2">
                <button
                    type="submit"
                    disabled={processing}
                    className="rounded-lg bg-primary px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-primary-dark disabled:opacity-60"
                >
                    {processing ? 'Mengirim…' : 'Kirim balasan'}
                </button>

                <button
                    type="button"
                    onClick={() => onSelesai?.()}
                    className="rounded-lg px-3 py-2 text-xs font-semibold text-muted transition hover:bg-linen hover:text-charcoal"
                >
                    Batal
                </button>
            </div>
        </form>
    );
}

/**
 * Formulir tulis/ubah ulasan.
 *
 * Pemilih bintang SELALU terlihat, bahkan bagi pengunjung yang belum masuk —
 * hanya tombol kirimnya yang diganti ajakan masuk. Menyembunyikan bintangnya
 * membuat bagian ini terasa seperti kotak kosong; memperlihatkannya
 * menjelaskan apa yang bisa dilakukan setelah punya akun.
 */
function FormulirUlasan({ resepId, ulasanSaya, masuk, penulisResep }) {
    const mengubah = Boolean(ulasanSaya);

    const { data, setData, post, patch, processing, errors, reset } = useForm({
        rating: ulasanSaya?.rating ?? 0,
        komentar: ulasanSaya?.komentar ?? '',
    });

    // Penulis resep tidak boleh mengulas karyanya sendiri. Pesannya dijelaskan,
    // bukan hanya formulir yang hilang tanpa keterangan.
    if (penulisResep) {
        return (
            <div className="rounded-2xl border border-dashed border-border bg-surface p-5">
                <p className="font-body text-sm leading-relaxed text-muted">
                    Anda penulis resep ini. Ulasan dari pembaca akan muncul di sini, dan Anda
                    bisa membalasnya.
                </p>
            </div>
        );
    }

    const kirim = (e) => {
        e.preventDefault();

        const opsi = {
            preserveScroll: true,
            onSuccess: () => {
                if (!mengubah) reset('komentar');
            },
        };

        if (mengubah) {
            patch(route('ulasan.update', ulasanSaya.id), opsi);
        } else {
            post(route('ulasan.store', resepId), opsi);
        }
    };

    return (
        <form
            onSubmit={kirim}
            className="rounded-2xl border border-border bg-surface p-5 shadow-card"
        >
            <h3 className="font-display text-sm font-bold text-charcoal">
                {mengubah ? 'Ubah ulasan Anda' : 'Beri penilaian'}
            </h3>

            <div className="mt-3">
                <PilihBintang
                    nilai={data.rating}
                    onChange={(bintang) => setData('rating', bintang)}
                />
            </div>

            {errors.rating && (
                <p className="mt-2 font-body text-xs text-danger">{errors.rating}</p>
            )}

            {masuk ? (
                <>
                    <label
                        htmlFor="komentar-ulasan"
                        className="mt-4 block font-body text-xs font-semibold text-charcoal"
                    >
                        Komentar <span className="font-normal text-muted">(opsional)</span>
                    </label>

                    <textarea
                        id="komentar-ulasan"
                        value={data.komentar}
                        onChange={(e) => setData('komentar', e.target.value)}
                        rows={4}
                        maxLength={2000}
                        placeholder="Bagaimana hasil masaknya menurut Anda?"
                        className="mt-1.5 w-full rounded-xl border border-border bg-warm-cream px-3.5 py-2.5 font-body text-sm text-charcoal shadow-sm transition placeholder:text-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    />

                    {errors.komentar && (
                        <p className="mt-1 font-body text-xs text-danger">{errors.komentar}</p>
                    )}

                    <button
                        type="submit"
                        disabled={processing || data.rating === 0}
                        className="mt-3 w-full rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-dark disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {processing
                            ? 'Mengirim…'
                            : mengubah
                              ? 'Simpan perubahan'
                              : 'Kirim ulasan'}
                    </button>
                </>
            ) : (
                <>
                    <p className="mt-4 font-body text-sm leading-relaxed text-muted">
                        Masuk dulu untuk mengirim penilaian Anda.
                    </p>

                    <Link
                        href={route('login')}
                        className="mt-3 block w-full rounded-xl bg-primary px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-primary-dark"
                    >
                        Masuk
                    </Link>
                </>
            )}
        </form>
    );
}
