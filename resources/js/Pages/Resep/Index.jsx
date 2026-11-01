import ChipKategori from '@/Components/ChipKategori';
import FilterResep from '@/Components/FilterResep';
import FormPencarian from '@/Components/FormPencarian';
import Pagination from '@/Components/Pagination';
import RecipeCard from '@/Components/RecipeCard';
import SiteFooter from '@/Components/SiteFooter';
import SiteHeader from '@/Components/SiteHeader';
import { IconArrowLeft } from '@/Components/Icons';
import { Head, Link } from '@inertiajs/react';

/**
 * Direktori seluruh resep yang tayang.
 *
 * Halaman ini adalah tujuan tombol "Lihat Semua" di beranda. Bedanya dengan
 * beranda hanya pada cakupan: beranda memperlihatkan empat kartu pertama
 * sebagai pratinjau, di sini seluruh resep tersedia berkat paginator, dan
 * saringan kategori serta kata kunci tetap berfungsi sama persis.
 */
export default function ResepIndex({
    auth,
    canLogin,
    canRegister,
    resep,
    kategoriTersedia = [],
    kategoriAktif = null,
    kataCari = '',
    urutkan = 'terbaru',
    kesulitan = '',
    kesulitanTersedia = [],
    maksMenit = null,
    minRating = null,
    bahanId = null,
    bahanTersedia = [],
}) {
    const labelKategori = Object.fromEntries(kategoriTersedia.map((k) => [k.value, k.label]));
    const kartu = resep?.data ?? [];

    const parameterUrut = (opsi) => {
        const p = {};
        if (kataCari) p.q = kataCari;
        if (kategoriAktif) p.kategori = kategoriAktif;
        if (kesulitan) p.kesulitan = kesulitan;
        if (maksMenit) p.maks_menit = maksMenit;
        if (minRating) p.min_rating = minRating;
        if (bahanId) p.bahan_id = bahanId;
        if (opsi !== 'terbaru') p.urutkan = opsi;
        return p;
    };

    const pesanKosong = kataCari || kategoriAktif
        ? `Tidak ada resep${kataCari ? ` untuk “${kataCari}”` : ''}${
              kategoriAktif ? ` pada kategori ${labelKategori[kategoriAktif] ?? kategoriAktif}` : ''
          }. Coba kata kunci atau kategori lain.`
        : 'Belum ada resep terbit saat ini.';

    return (
        <>
            <Head title="Semua Resep Nusantara" />

            <div className="min-h-screen bg-warm-cream text-charcoal">
                <SiteHeader
                    user={auth?.user}
                    canLogin={canLogin}
                    canRegister={canRegister}
                    beranda={false}
                />

                <main className="mx-auto max-w-shell px-4 py-10 sm:px-6 lg:px-8">
                    {/* Jalan pulang — hanya untuk TAMU. Sejak tautan nav di
                        beranda dihapus, pengunjung tanpa akun tidak punya cara
                        lain kembali selain mengetik URL. Pengguna yang sudah
                        masuk tidak membutuhkannya: logo di bilah atas sudah
                        membawa ke beranda, dan tombol ini hanya menambah satu
                        baris sebelum judul yang setiap kali harus dilewati. */}
                    {!auth?.user && (
                        <Link
                            href="/"
                            className="inline-flex items-center gap-2 font-body text-sm font-semibold text-muted transition hover:text-primary"
                        >
                            <IconArrowLeft className="h-4 w-4" />
                            Kembali ke beranda
                        </Link>
                    )}

                    <span
                        className={`block font-mono text-xs font-semibold uppercase tracking-wider text-primary ${
                            auth?.user ? '' : 'mt-6'
                        }`}
                    >
                        Direktori
                    </span>
                    <h1 className="mt-2 text-3xl font-extrabold tracking-tight">
                        Semua Resep Nusantara
                    </h1>
                    <p className="mt-3 max-w-2xl font-body text-base leading-relaxed text-muted">
                        Seluruh resep yang sudah tayang, lengkap dengan perkiraan gizi per
                        porsi. Saring berdasarkan kategori atau kata kunci apa pun.
                    </p>

                    <div className="mt-8">
                        <FormPencarian tujuan="/resep" kataCari={kataCari} kategoriAktif={kategoriAktif} />
                    </div>

                    <ChipKategori
                        tujuan="/resep"
                        kategoriTersedia={kategoriTersedia}
                        kategoriAktif={kategoriAktif}
                        kataCari={kataCari}
                    />

                    <FilterResep
                        tujuan="/resep"
                        kataCari={kataCari}
                        kategoriAktif={kategoriAktif}
                        urutkan={urutkan}
                        kesulitan={kesulitan}
                        kesulitanTersedia={kesulitanTersedia}
                        maksMenit={maksMenit}
                        minRating={minRating}
                        bahanId={bahanId}
                        bahanTersedia={bahanTersedia}
                    />

                    {/* Pilihan urutan tampilan */}
                    <div className="mt-6 flex items-center justify-between border-b border-border pb-4">
                        <p className="font-body text-xs text-muted">
                            Menampilkan{' '}
                            <strong className="font-semibold text-charcoal">
                                {resep?.total ?? kartu.length}
                            </strong>{' '}
                            resep
                        </p>

                        <div className="flex items-center gap-1.5 text-xs">
                            <span className="font-body text-muted">Urutkan:</span>
                            <Link
                                href={route('resep.index', parameterUrut('terbaru'))}
                                preserveScroll
                                className={`rounded-lg px-2.5 py-1 font-semibold transition ${
                                    urutkan === 'terbaru'
                                        ? 'bg-charcoal text-warm-cream'
                                        : 'text-muted hover:bg-linen hover:text-charcoal'
                                }`}
                            >
                                Terbaru
                            </Link>
                            <Link
                                href={route('resep.index', parameterUrut('rating'))}
                                preserveScroll
                                className={`rounded-lg px-2.5 py-1 font-semibold transition ${
                                    urutkan === 'rating'
                                        ? 'bg-charcoal text-warm-cream'
                                        : 'text-muted hover:bg-linen hover:text-charcoal'
                                }`}
                            >
                                Rating Tertinggi
                            </Link>
                        </div>
                    </div>

                    {kartu.length > 0 ? (
                        <>
                            <div className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                {kartu.map((recipe) => (
                                    <RecipeCard
                                        key={recipe.slug}
                                        recipe={recipe}
                                        labelKategori={labelKategori}
                                    />
                                ))}
                            </div>

                            <Pagination halaman={resep} />
                        </>
                    ) : (
                        <p className="mt-8 rounded-2xl border border-dashed border-border bg-surface px-6 py-12 text-center font-body text-sm text-muted">
                            {pesanKosong}
                        </p>
                    )}
                </main>

                <SiteFooter canLogin={canLogin} canRegister={canRegister} beranda={false} />
            </div>
        </>
    );
}
