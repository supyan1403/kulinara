import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            /**
             * Palet resmi KULINARA — dokumen "Brand & Design Essentials v1.0".
             * Peran warna mengikuti spesifikasi: primary = identitas & tombol utama,
             * terracotta = aksen kuliner, sage/mint = aksen nutrisi.
             */
            colors: {
                primary: '#044d3c',
                'primary-dark': '#003428',
                terracotta: '#d96b43',
                'terracotta-dark': '#b8542f',
                'sage-dark': '#2e7d67',
                'mint-spark': '#10b981',
                'mint-light': '#e8f8f2',
                charcoal: '#1a2e26',
                'warm-cream': '#fbf9f4',
                linen: '#f4f0e8',
                surface: '#ffffff',
                // #676f6b, bukan #707974 yang lama. Token ini dipakai untuk
                // seluruh teks sekunder, dan #707974 gagal WCAG AA di empat
                // latar yang dipakai situs ini: 3.95:1 di linen, 4.09 di
                // mint-light, 4.27 di warm-cream, 4.49 di putih — semuanya di
                // bawah ambang 4.5:1 untuk teks normal. Nilai baru lolos di
                // keempatnya (4.55 / 4.71 / 4.91 / 5.17) sambil tetap terbaca
                // sebagai abu-abu redup yang sama, hanya sedikit lebih dalam.
                muted: '#676f6b',
                border: '#e5e3dd',
                danger: '#ba1a1a',
                'danger-light': '#ffdad6',
            },

            /**
             * Tiga font dengan peran terpisah (keputusan: ketiganya):
             *  - sans  → Plus Jakarta Sans : heading, label, nav, tombol
             *  - body  → Inter             : teks panjang (instruksi masak)
             *  - mono  → JetBrains Mono    : angka gizi, hex, nilai AKG
             */
            fontFamily: {
                sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
                body: ['Inter', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },

            /**
             * Elevation bertekstur hangat dengan forest undertone (DESIGN.md).
             * Tiga tingkat: card (kartu), raised (dropdown), overlay (modal/drawer).
             */
            boxShadow: {
                card: '0 1px 2px rgba(26, 46, 38, 0.04), 0 4px 12px rgba(26, 46, 38, 0.05)',
                raised: '0 4px 16px rgba(26, 46, 38, 0.08), 0 2px 4px rgba(26, 46, 38, 0.04)',
                overlay: '0 16px 48px rgba(26, 46, 38, 0.16), 0 4px 12px rgba(26, 46, 38, 0.08)',
            },

            maxWidth: {
                shell: '80rem', // 1280px — lebar maksimum grid 12 kolom
            },

            /**
             * Satu keyframe reveal untuk landing page — sengaja hanya satu
             * supaya hemat dan konsisten. Dikendalikan komponen <Reveal/>.
             */
            keyframes: {
                fadeUp: {
                    '0%': { opacity: '0', transform: 'translateY(20px)' },
                    '100%': { opacity: '1', transform: 'none' },
                },
            },
            animation: {
                'fade-up': 'fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both',
            },
        },
    },

    plugins: [forms],
};
