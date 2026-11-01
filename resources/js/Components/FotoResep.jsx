/**
 * Nilai kolom `gambar` disimpan RELATIF terhadap root publik —
 * "images/resep/xyz.jpg", tanpa garis miring di depan. Konvensi itu datang
 * dari StudioController::simpanGambar(), yang juga mengandalkan awalan persis
 * itu saat menghapus berkas, jadi nilainya tidak boleh diubah di database.
 *
 * Yang harus ada adalah satu aturan pengubah di sini. Tanpa garis miring
 * di depan, sebuah halaman bersarang seperti /resep/{slug} menafsirkan
 * "images/…" sebagai /resep/images/… dan fotonya berujung 404 — beranda lolos
 * hanya karena base URL-nya "/".
 */
const sumberGambar = (gambar) => {
    if (!gambar) return gambar;
    if (gambar.startsWith('/') || /^(https?:)?\/\//.test(gambar)) return gambar;
    return `/${gambar}`;
};

/**
 * Bungkus foto resep: tampilkan gambarnya, atau placeholder kalau belum
 * ada. Tanpa teks apa pun supaya resep berfoto-sebagian tetap terlihat
 * seperti desain, bukan seperti gambar yang gagal dimuat.
 *
 * `eager` dipakai untuk gambar di atas viewport (mis. slideshow hero) supaya
 * tidak tertunda oleh strategi lazy-loading bawaan browser.
 */
export default function FotoResep({ recipe, imgClassName = '', eager = false }) {
    if (!recipe.gambar) {
        return (
            <div
                className="flex h-full w-full items-center justify-center bg-linen"
                role="img"
                aria-label={`Belum ada foto untuk ${recipe.judul}`}
            >
                <span className="flex h-12 w-12 items-center justify-center rounded-full bg-mint-light ring-1 ring-inset ring-primary/10">
                    <svg
                        className="h-6 w-6 text-sage-dark"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="1.5"
                        aria-hidden="true"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5a1.5 1.5 0 001.5-1.5V4.5a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 4.5v15a1.5 1.5 0 001.5 1.5zM6 10.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"
                        />
                    </svg>
                </span>
            </div>
        );
    }

    return (
        <img
            src={sumberGambar(recipe.gambar)}
            alt={recipe.judul}
            loading={eager ? 'eager' : 'lazy'}
            className={`object-cover ${imgClassName || 'h-full w-full'}`}
        />
    );
}
