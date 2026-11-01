import { IconStar } from '@/Components/Icons';

/**
 * Deretan bintang untuk MEMBACA nilai.
 *
 * Tidak bisa diklik — untuk yang bisa diklik, pakai `PilihBintang`. Dua
 * komponen dipisah karena keduanya punya tanggung jawab berbeda: yang ini
 * murni tata letak, yang itu menangani keyboard, fokus, dan ARIA.
 *
 * Nilai pecahan dibulatkan ke bintang terdekat untuk penggambarannya: 4.4
 * tampil sebagai empat bintang penuh, 4.5 ke atas tampil lima. Angka
 * sebenarnya tetap ditampilkan di sebelah bintang, jadi pembulatan visual
 * tidak menyembunyikan apa pun.
 */
export default function BintangRating({
    nilai = 0,
    ukuran = 'sm',
    className = '',
    tampilkanAngka = false,
    jumlah = null,
}) {
    const ukuranKelas = {
        sm: 'h-4 w-4',
        md: 'h-5 w-5',
        lg: 'h-7 w-7',
    }[ukuran];

    const bulat = Math.round(Number(nilai) || 0);

    return (
        <span className={`inline-flex items-center gap-1 ${className}`}>
            <span className="inline-flex" aria-hidden="true">
                {[1, 2, 3, 4, 5].map((bintang) => (
                    <IconStar
                        key={bintang}
                        isi={bintang <= bulat}
                        className={`${ukuranKelas} ${
                            bintang <= bulat ? 'text-amber-500' : 'text-border'
                        }`}
                    />
                ))}
            </span>

            {/* Angka dan jumlah ulasan dibaca pembaca layar; deretan bintang
                di atas sengaja `aria-hidden` supaya tidak dibacakan sebagai
                lima entri tanpa arti. */}
            <span className="sr-only">
                {`Rata-rata ${Number(nilai).toFixed(1)} dari 5`}
                {jumlah !== null ? `, dari ${jumlah} ulasan` : ''}
            </span>

            {(tampilkanAngka || jumlah !== null) && (
                <span className="text-xs font-semibold text-charcoal">
                    {Number(nilai).toFixed(1)}
                    {jumlah !== null && (
                        <span className="font-normal text-muted"> ({jumlah})</span>
                    )}
                </span>
            )}
        </span>
    );
}
