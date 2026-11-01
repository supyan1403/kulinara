import { IconStar } from '@/Components/Icons';
import { useState } from 'react';

/**
 * Pemilih bintang yang bisa diklik — dipakai di formulir tulis/ubah ulasan.
 *
 * Dibangun dari `role="radiogroup"` + lima `role="radio"`, bukan lima
 * `<button>` biasa. Alasannya: bagi pembaca layar, "pilih 1 sampai 5" adalah
 * pertanyaan pilihan tunggal, dan itulah yang diumumkan. Dengan tombol biasa,
 * pengguna hanya mendengar "tombol, tombol, tombol" tanpa tahu sedang memilih
 * apa — dan tanpa tahu mana yang sedang terpilih.
 *
 * Setiap radio tetap bisa difokus dengan Tab (bukan roving tabindex), jadi
 * pengguna keyboard tidak perlu menebak cara berpindah. Panah kiri/kanan tetap
 * didukung karena itu kebiasaan yang sudah tertanam untuk radiogroup.
 */
export default function PilihBintang({
    nilai = 0,
    onChange,
    jumlah = 5,
    ukuran = 'lg',
}) {
    // Bintang yang sedang disorot tetikus. Terpisah dari `nilai` supaya
    // menggerakkan kursor tidak mengirim perubahan apa pun — yang tersimpan
    // hanya yang benar-benar ditekan.
    const [sorot, setSorot] = useState(0);

    const ukuranKelas = {
        md: 'h-6 w-6',
        lg: 'h-8 w-8',
    }[ukuran];

    const dipakai = sorot || nilai;

    const pilih = (bintang) => {
        onChange?.(bintang);
    };

    const saatTombol = (e) => {
        if (e.key === 'ArrowRight' || e.key === 'ArrowUp') {
            e.preventDefault();
            pilih(Math.min((nilai || 0) + 1 || 1, jumlah));
        }

        if (e.key === 'ArrowLeft' || e.key === 'ArrowDown') {
            e.preventDefault();
            pilih(Math.max((nilai || 1) - 1, 1));
        }
    };

    const label = [
        'Sangat kurang',
        'Kurang',
        'Cukup',
        'Enak',
        'Sangat enak',
    ];

    return (
        <div className="flex items-center gap-3">
            <div
                role="radiogroup"
                aria-label="Jumlah bintang"
                className="inline-flex items-center gap-0.5"
                onMouseLeave={() => setSorot(0)}
            >
                {Array.from({ length: jumlah }, (_, i) => i + 1).map((bintang) => (
                    <button
                        key={bintang}
                        type="button"
                        role="radio"
                        aria-checked={nilai === bintang}
                        aria-label={`${bintang} bintang — ${label[bintang - 1] ?? ''}`}
                        onClick={() => pilih(bintang)}
                        onMouseEnter={() => setSorot(bintang)}
                        onFocus={() => setSorot(bintang)}
                        onKeyDown={saatTombol}
                        className="rounded-md p-0.5 transition hover:scale-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-warm-cream motion-reduce:hover:scale-100"
                    >
                        <IconStar
                            isi={bintang <= dipakai}
                            className={`${ukuranKelas} transition-colors ${
                                bintang <= dipakai ? 'text-amber-500' : 'text-border'
                            }`}
                        />
                    </button>
                ))}
            </div>

            {/* Label tekstual di samping bintang: "Sangat enak" jauh lebih
                mudah ditafsirkan daripada "5". Membantu juga saat warnanya
                tidak terlihat — misalnya bagi pengguna dengan buta warna. */}
            {dipakai > 0 && (
                <span className="text-sm font-semibold text-charcoal">
                    {label[dipakai - 1]}
                </span>
            )}
        </div>
    );
}
