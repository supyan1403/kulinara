/**
 * Ikon SVG inline yang dipakai lebih dari satu halaman.
 *
 * Tidak ada font eksternal dan tidak ada pustaka ikon pihak ketiga — setiap
 * ikon menggambar dirinya sendiri dengan viewBox 24x24, stroke 1.5, dan ujung
 * membulat, mengikuti bahasa visual Heroicons Outline yang dipakai seluruh
 * aplikasi. Warna ikon selalu diwarisi dari `currentColor`, sehingga ikon yang
 * sama bisa terlihat hijau di satu kartu dan terakota di kartu lain.
 *
 * CATATAN PENTING: nama berkas ini adalah `Icons.jsx` (huruf kapital I).
 * Windows memakai sistem berkas yang tidak membedakan huruf besar/kecil, jadi
 * file `icons.jsx` (huruf kecil) tidak pernah bisa menjadi berkas terpisah —
 * menulisnya akan diam-diam MENIMPA berkas ini. Selalu impor sebagai
 * `@/Components/Icons`.
 */

/** Siluet dasar semua ikon di berkas ini. */
export function Svg({ children, className = 'h-5 w-5' }) {
    return (
        <svg
            className={className}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.5"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
        >
            {children}
        </svg>
    );
}

/* ─── Arah ─── */

export const IconArrowLeft = (p) => (
    <Svg {...p}>
        <path d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
    </Svg>
);

export const IconArrowRight = (p) => (
    <Svg {...p}>
        <path d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
    </Svg>
);

export const IconArrowUp = (p) => (
    <Svg {...p}>
        <path d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18" />
    </Svg>
);

export const IconArrowDown = (p) => (
    <Svg {...p}>
        <path d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" />
    </Svg>
);

export const IconChevronDown = (p) => (
    <Svg {...p}>
        <path d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
    </Svg>
);

/* ─── Kategori kuliner & gizi ─── */

export const IconClock = (p) => (
    <Svg {...p}>
        <path d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
    </Svg>
);

export const IconFire = (p) => (
    <Svg {...p}>
        <path d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48A8.25 8.25 0 0012 11.25a8.25 8.25 0 00.645 3.587z" />
    </Svg>
);

/* ─── Aksi ─── */

export const IconPlus = (p) => (
    <Svg {...p}>
        <path d="M12 4.5v15m7.5-7.5h-15" />
    </Svg>
);

export const IconTrash = (p) => (
    <Svg {...p}>
        <path d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
    </Svg>
);

export const IconPencil = (p) => (
    <Svg {...p}>
        <path d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
    </Svg>
);

export const IconPhoto = (p) => (
    <Svg {...p}>
        <path d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 19.5h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z" />
    </Svg>
);

export const IconSearch = (p) => (
    <Svg {...p}>
        <path d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
    </Svg>
);

export const IconCheck = (p) => (
    <Svg {...p}>
        <path d="M4.5 12.75l6 6 9-13.5" />
    </Svg>
);

/**
 * Ikon penanda (bookmark).
 *
 * `isi` mengisi bidangnya — dipakai untuk keadaan "sudah tersimpan" pada
 * tombol Simpan. Perbedaannya sengaja dibuat lewat BENTUK, bukan warna
 * saja: pengguna buta warna tetap bisa membedakan tombol yang sudah aktif
 * dari yang belum.
 */
export const IconBookmark = ({ isi = false, ...p }) => (
    <Svg {...p}>
        <path
            d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z"
            fill={isi ? 'currentColor' : 'none'}
        />
    </Svg>
);

/**
 * Ikon hati — tombol "suka" (like) untuk resep dan profil.
 *
 * Sama seperti IconBookmark, `isi` mengubah bidangnya jadi penuh: keadaan
 * "sudah disukai" terbedakan lewat BENTUK, bukan warna saja, supaya tetap
 * terbaca bagi pengguna buta warna.
 */
export const IconHeart = ({ isi = false, ...p }) => (
    <Svg {...p}>
        <path
            d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"
            fill={isi ? 'currentColor' : 'none'}
        />
    </Svg>
);

/* ─── Akun ─── */

/** Siluet orang — dipakai menu akun dan tautan profil. */
export const IconUser = (p) => (
    <Svg {...p}>
        <path d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
    </Svg>
);

/**
 * Pintu keluar.
 *
 * Arah panahnya dibalik (`scale-x-[-1]` pada pemakaian) bila ingin menunjuk
 * ke kiri pada bahasa RTL — untuk sekarang arah kanan sudah benar.
 */
export const IconLogout = (p) => (
    <Svg {...p}>
        <path d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
    </Svg>
);

/* ─────────────── Ulasan ─────────────── */

/**
 * Bintang penilaian.
 *
 * `isi` menentukan apakah bintangnya penuh atau hanya garis tepi. Berbeda
 * dari `IconBookmark` yang memakai satu `<path>` yang sama untuk kedua
 * keadaan, di sini bentuknya memang dua jalur berbeda: versi penuh memakai
 * `<path>` yang sudah tertutup, versi kosong memakai jalur bergaris yang sama
 * dengan ikon outline lain. Hasilnya bintang terisi terlihat padat, bukan
 * sekadar garis yang ditebalkan — dan itu yang membedakannya dari bintang
 * kosong saat dilihat sekilas.
 */
export const IconStar = ({ isi = false, ...p }) => (
    <svg
        className={p.className ?? 'h-5 w-5'}
        viewBox="0 0 24 24"
        fill={isi ? 'currentColor' : 'none'}
        stroke="currentColor"
        strokeWidth={isi ? 0 : 1.5}
        strokeLinecap="round"
        strokeLinejoin="round"
        aria-hidden="true"
    >
        <path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
    </svg>
);

/**
 * Balon percakapan — menandai bagian ulasan dan balasan.
 */
export const IconChat = (p) => (
    <Svg {...p}>
        <path d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
    </Svg>
);
