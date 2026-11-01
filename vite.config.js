import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.jsx',
            refresh: true,
        }),
        react(),
    ],

    /**
     * Terbuka ke jaringan lokal supaya bisa dibuka dari HP.
     *
     * `host: true` membuat Vite mendengarkan di semua interface (0.0.0.0),
     * bukan hanya 127.0.0.1 — tanpa ini HP yang membuka
     * http://192.168.1.7:8000 akan mendapat halaman tanpa CSS: HTML-nya datang
     * dari `php artisan serve`, tapi aset Vite tidak bisa diambil dari laptop.
     *
     * `hmr.host` dipatok ke IP LAN, bukan `localhost`: skrip HMR dikirim ke
     * browser dan `localhost` di HP menunjuk ke HP itu sendiri, sehingga
     * hot-reload diam-diam gagal tanpa pesan error.
     */
    server: {
        host: true,
        hmr: {
            host: '192.168.1.7',
        },
    },
});
