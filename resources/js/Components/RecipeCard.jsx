import AvatarInisial from '@/Components/AvatarInisial';
import BintangRating from '@/Components/BintangRating';
import FotoResep from '@/Components/FotoResep';
import { IconBookmark, IconCheck, IconClock, IconFire, IconHeart } from '@/Components/Icons';
import { Link, router, usePage } from '@inertiajs/react';

/**
 * Apakah resep memenuhi syarat badge "Standar".
 *
 * Syaratnya sama dengan `Resep::standarTerpenuhi()` di server: gizi
 * dihitung otomatis, cakupan perhitungan 100%, dan hasil akhir tercatat.
 * Dihitung di sini dari field mentah yang sudah terserialisasi — bukan
 * lewat method model, karena method tidak ikut terkirim ke React.
 *
 * @param {object} recipe
 * @returns {boolean}
 */
const standarTerpenuhi = (recipe) =>
    recipe.gizi_mode === 'otomatis' &&
    Number(recipe.gizi_cakupan) === 100 &&
    recipe.hasil_total != null;

/**
 * Kartu resep — kartu ini yang dipakai beranda dan direktori /resep, dan
 * seluruh permukaannya menjadi satu tautan menuju halaman detail.
 *
 * Sebelumnya kartu ini bukan tautan apa pun: beranda memamerkan "368 resep"
 * tetapi tidak satu pun bisa dibuka. Menjadikan seluruh kartu sebagai satu
 * tautan (bukan hanya judulnya) membuat target ketukan di ponsel selebar
 * kartu — area sebesar 44px jauh lebih ramah daripada sekadar teks judul.
 */
export default function RecipeCard({
    recipe,
    labelKategori = {},
    className = '',
    tampilkanPenulis = true,
    disimpan = false,
}) {
    // Tag = label kategori dari enum (bukan value mentahnya).
    //
    // `daerah` sengaja TIDAK ditampilkan di sini, di halaman detail, maupun
    // di pratinjau Studio. Kolomnya tetap ada dan tetap diisi lewat form
    // Studio, tapi hanya 173 dari 368 resep terbit yang mengisinya — chip
    // yang muncul di separuh kartu dan lenyap di sisanya terbaca sebagai
    // cacat, bukan pilihan desain.
    //
    // Pencarian wilayah masih menemukan resepnya, TAPI lewat teks deskripsi
    // yang menyebut nama daerah — bukan lewat kolom ini. Kolom generated
    // `pencarian` hanya memuat judul dan deskripsi.
    const tags = [labelKategori[recipe.kategori]].filter(Boolean);

    // ── Suka (like) ──
    const { auth, sukaSaya } = usePage().props;
    const jumlahSuka = Number(recipe.jumlah_suka);
    const jumlahSimpanan = Number(recipe.jumlah_simpanan);
    const punyaResep = auth?.user?.id === recipe.user_id;
    const sudahSuka = Array.isArray(sukaSaya?.resep) && sukaSaya.resep.includes(recipe.id);

    // Kedua aksi di dalam kartu wajib mematikan event: kartu SENDIRI adalah
    // satu tautan besar, jadi tanpa preventDefault + stopPropagation klik
    // hati/penulis ikut membuka halaman resep yang sedang diklik.
    const klikSuka = (e) => {
        e.preventDefault();
        e.stopPropagation();

        // Tamu diarahkan ke halaman masuk — tombol tetap tampil agar ia
        // tahu fiturnya ada, bukan kehilangan alasan untuk mendaftar.
        if (!auth?.user) {
            router.get(route('login'));
            return;
        }

        router.post(route('resep.suka', recipe.slug), {}, { preserveScroll: true });
    };

    const bukaProfil = (e) => {
        e.preventDefault();
        e.stopPropagation();
        router.get(route('profil.show', recipe.penulis.username));
    };

    return (
        <Link
            href={route('resep.show', recipe.slug)}
            className={`block h-full rounded-2xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 ${className}`}
        >
            <article className="group flex h-full flex-col overflow-hidden rounded-2xl border border-border bg-surface shadow-card transition duration-300 hover:-translate-y-1 hover:shadow-raised">
                <div className="relative aspect-[4/3] overflow-hidden bg-linen">
                    <FotoResep
                        recipe={recipe}
                        imgClassName="h-full w-full transition duration-500 group-hover:scale-105"
                    />

                    {/* Penanda waktu — pill di atas foto */}
                    <span className="absolute left-3 top-3 inline-flex items-center gap-1.5 rounded-full bg-surface/90 px-2.5 py-1 text-xs font-semibold text-charcoal backdrop-blur-sm ring-1 ring-inset ring-border">
                        <IconClock className="h-3.5 w-3.5 text-primary" />
                        {recipe.waktu_masak_menit} mnt
                    </span>

                    {/* Badge Standar — di pojok kanan atas foto */}
                    {standarTerpenuhi(recipe) && (
                        <span
                            title="Gizi dihitung otomatis, cakupan penuh, hasil akhir tercatat — resep bisa direproduksi."
                            className="absolute right-3 top-3 inline-flex items-center gap-1 rounded-full bg-sage-dark px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-white backdrop-blur-sm ring-1 ring-inset ring-sage-dark/20"
                        >
                            <IconCheck className="h-3 w-3" />
                            Standar
                        </span>
                    )}
                </div>

                <div className="flex flex-1 flex-col p-5">
                    {/*
                        Kontainer ini dulu memakai `min-h-12` untuk menampung
                        dua baris tag — "Kalimantan Selatan" + kategori kadang
                        membungkus, dan tanpa tinggi tetap judul satu kartu
                        duduk 26px lebih rendah dari judul kartu sebelahnya.

                        Sejak `daerah` tidak lagi ditampilkan, tag tinggal satu.
                        Label kategori terpanjang ("Nasi & Karbohidrat") masih
                        muat sebaris pada setiap lebar kartu, jadi tingginya
                        sudah pasti seragam tanpa tinggi tetap.
                    */}
                    <div className="flex flex-wrap gap-1.5">
                        {tags.map((tag) => (
                            <span
                                key={tag}
                                className="rounded-full bg-linen px-2.5 py-0.5 text-[11px] font-semibold text-charcoal ring-1 ring-inset ring-border"
                            >
                                {tag}
                            </span>
                        ))}
                    </div>

                    <h3 className="mt-3 text-base font-bold leading-snug text-charcoal">
                        {recipe.judul}
                    </h3>

                    {/* Rating — hanya muncul kalau sudah ada yang menilai.
                        Kartu dengan "0.0 (0)" terbaca sebagai resep buruk,
                        padahal artinya belum ada penilaian sama sekali. */}
                    {Number(recipe.jumlah_ulasan) > 0 && (
                        <div className="mt-2">
                            <BintangRating
                                nilai={recipe.rata_rating}
                                jumlah={recipe.jumlah_ulasan}
                            />
                        </div>
                    )}

                    <p className="mt-1.5 font-body text-sm leading-relaxed text-muted">
                        {recipe.deskripsi}
                    </p>

                    {/*
                        Baris identitas penulis + tombol suka. Dibungkus satu
                        `mt-auto` bersama baris gizi supaya keduanya menempel
                        ke dasar kartu sebagai satu blok — tanpa ini jarak
                        antara nama penulis dan angka gizi ikut melebar mengikuti
                        panjang deskripsi.
                    */}
                    <div className="mt-auto">
                        {/*
                            Baris like + simpan: SELALU tampil di setiap kartu,
                            termasuk saat angkanya 0. Menyembunyikan baris ini
                            saat nol membuat kartu tidak konsisten tingginya dan
                            pembaca kehilangan petunjuk bahwa fitur suka/simpan
                            memang ada — angka 0 sendiri sudah jelas artinya.
                        */}
                        <div className="mt-3 flex items-center gap-2">
                                {tampilkanPenulis && recipe.penulis?.name && (
                                    recipe.penulis.username ? (
                                        <button
                                            type="button"
                                            onClick={bukaProfil}
                                            className="flex min-w-0 items-center gap-2 text-left"
                                        >
                                            <AvatarInisial
                                                nama={recipe.penulis.name}
                                                foto={recipe.penulis.avatar_path}
                                                ukuran="sm"
                                            />
                                            <span className="truncate text-xs font-semibold text-muted transition hover:text-charcoal">
                                                {recipe.penulis.name}
                                            </span>
                                        </button>
                                    ) : (
                                        <span className="flex min-w-0 items-center gap-2">
                                            <AvatarInisial
                                                nama={recipe.penulis.name}
                                                foto={recipe.penulis.avatar_path}
                                                ukuran="sm"
                                            />
                                            <span className="truncate text-xs font-semibold text-muted">
                                                {recipe.penulis.name}
                                            </span>
                                        </span>
                                    )
                                )}

                                {/* Penulis sendiri tidak bisa menyukai resepnya —
                                    hanya angka reputasinya yang tampil, tanpa
                                    tombol. Angka 0 tetap ditampilkan supaya
                                    setiap kartu memuat pasangan like + simpan. */}
                                {punyaResep && (
                                    <span
                                        className="ml-auto inline-flex shrink-0 items-center gap-1.5 text-xs text-muted"
                                        title="Jumlah orang yang menyukai resep ini"
                                    >
                                        <IconHeart isi className="h-4 w-4 text-terracotta" />
                                        <span className="font-mono font-semibold tabular-nums">
                                            {jumlahSuka}
                                        </span>
                                    </span>
                                )}

                                {!punyaResep && (
                                    <button
                                        type="button"
                                        onClick={klikSuka}
                                        aria-pressed={sudahSuka}
                                        aria-label={sudahSuka ? 'Batalkan suka pada resep ini' : 'Suka resep ini'}
                                        className={`ml-auto inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold transition ${
                                            sudahSuka
                                                ? 'text-terracotta hover:bg-terracotta/10'
                                                : 'text-muted hover:bg-linen hover:text-terracotta'
                                        }`}
                                    >
                                        <IconHeart isi={sudahSuka} className="h-4 w-4" />
                                        {Number.isFinite(jumlahSuka) && (
                                            <span className="font-mono tabular-nums">{jumlahSuka}</span>
                                        )}
                                    </button>
                                )}

                                {/* Save count — selalu tampil, angka 0 sekali
                                    pun. Pemilik tidak bisa klik simpan di kartu
                                    milik sendiri (sudah pasti ia simpan), jadi
                                    tampilkan angka statis saja. Pengunjung lain
                                    tidak ada tombol simpan di kartu ini. */}
                                <span
                                    className="ml-2 inline-flex shrink-0 items-center gap-1 text-xs text-muted"
                                    title="Jumlah orang yang menyimpan resep ini"
                                >
                                    <IconBookmark className="h-4 w-4" isi={disimpan} />
                                    <span className="font-mono font-semibold tabular-nums">
                                        {jumlahSimpanan}
                                    </span>
                                </span>
                            </div>

                        {/* Baris gizi — angka mono tabular; menempel ke dasar kartu */}
                        <div className="mt-3 flex items-center gap-2 border-t border-border pt-3">
                            <span className="inline-flex items-center gap-1.5">
                                <IconFire className="h-4 w-4 text-terracotta" />
                                <span className="font-mono text-sm font-bold tabular-nums text-primary">
                                    {Math.round(Number(recipe.energi_kkal))}
                                </span>
                                <span className="font-mono text-[11px] text-muted">kkal</span>
                            </span>

                            <span className="h-3 w-px bg-border" />

                            <span className="font-mono text-[11px] text-muted">
                                Protein{' '}
                                <span className="font-bold tabular-nums text-sage-dark">
                                    {Math.round(Number(recipe.protein_g))}g
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
            </article>
        </Link>
    );
}
