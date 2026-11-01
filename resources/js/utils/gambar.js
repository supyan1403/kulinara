/**
 * Ubah isi kolom `gambar` menjadi URL yang bisa dimuat browser.
 *
 * Seluruh isi kolom disimpan sebagai path relatif dari root aplikasi —
 * `images/landing/…` untuk foto bawaan dan `images/resep/…` untuk unggahan —
 * sehingga cukup menambahkan "/" di depan. Tanpa awalan "/", path seperti
 * `images/landing/x.jpg` akan salah saat dibaca dari halaman bersub-direktori
 * (mis. /studio-dapur).
 *
 * URL absolut atau domain luar diteruskan apa adanya.
 */
export function urlGambar(path) {
    if (!path) return null;

    if (/^(?:https?:)?\/\//.test(path) || path.startsWith('/')) {
        return path;
    }

    return `/${path.replace(/^\/+/, '')}`;
}
