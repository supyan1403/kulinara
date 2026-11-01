import { urlGambar } from '@/utils/gambar';

/**
 * Avatar pengguna.
 *
 * Dua wujud dalam satu komponen: foto profil bila pengguna mengunggahnya,
 * inisial nama bila belum. Inisial bukan sekadar hiasan — ia yang membuat akun
 * tetap terbedakan saat foto belum ada.
 *
 * Aturan inisial:
 *   - "Ahmad Fauzi"      -> "AF"  (huruf pertama dua kata pertama)
 *   - "Ahmad"            -> "A"   (satu kata, satu huruf)
 *   - "  " / null        -> "?"   (nama kosong tidak boleh menjatuhkan render)
 *
 * Nama dipecah pada spasi dan tanda hubung sekaligus: "Rina-Maria" adalah dua
 * kata, bukan satu, dan "RM" lebih informatif daripada "R".
 */
const inisial = (nama) => {
    const kata = String(nama ?? '')
        .trim()
        .split(/[\s-]+/)
        .filter(Boolean);

    if (kata.length === 0) return '?';

    if (kata.length === 1) {
        return kata[0].charAt(0).toUpperCase();
    }

    return (kata[0].charAt(0) + kata[1].charAt(0)).toUpperCase();
};

/**
 * Ukuran dinyatakan dalam kelas lengkap, bukan interpolasi string: Tailwind
 * memindai kelas secara statis, jadi `h-${ukuran}` tidak akan pernah masuk
 * hasil build.
 */
const UKURAN = {
    sm: 'h-7 w-7 text-[11px]',
    md: 'h-9 w-9 text-sm',
    lg: 'h-12 w-12 text-lg',
    xl: 'h-32 w-32 text-5xl',
};

/**
 * Avatar pengguna.
 *
 * Bila foto profil ada, tampilkan foto; bila tidak (atau foto gagal dimuat),
 * jatuh ke inisial nama. Inisial BUKAN sekadar hiasan — ia yang membuat akun
 * tetap terbedakan saat foto belum diunggah, dan tetap terlihat sama di daftar
 * komentar, nav, maupun halaman profil.
 *
 * Foto dirender sebagai <img> di dalam lingkaran berukuran sama dengan versi
 * inisial, sehingga tinggi baris navigasi tidak melompat saat foto muncul.
 *
 * Catatan: bila pengguna menghapus fotonya di tab lain, browser bisa sudah
 * menyimpan <img> yang 404. Itu tidak menjatuhkan halaman — yang tampak hanya
 * lingkaran kosong sesaat, dan hilang pada muat ulang berikutnya.
 */
export default function AvatarInisial({
    nama,
    ukuran = 'md',
    foto = null,
    className = '',
    posisi = 'center',
}) {
    const teks = inisial(nama);
    const ukuranKelas = UKURAN[ukuran] ?? UKURAN.md;
    const sumber = urlGambar(foto);

    return (
        <span
            aria-hidden="true"
            className={`inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary font-display font-bold tracking-tight text-white ring-1 ring-inset ring-primary-dark/20 ${ukuranKelas} ${className}`}
        >
            {sumber ? (
                // alt kosong: nama sudah dibacakan di sebelah avatar, dan
                // mengulanginya membuat pembaca layar menyebut nama dua kali.
                <img
                    src={sumber}
                    alt=""
                    loading="lazy"
                    className="h-full w-full object-cover"
                    style={{ objectPosition: posisi }}
                />
            ) : (
                teks
            )}
        </span>
    );
}

export { inisial };
