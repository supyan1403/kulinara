{{--
    Kerangka halaman kesalahan yang berdiri sendiri.

    Sengaja TIDAK memakai @vite. Halaman 500 harus tetap tampil justru
    ketika build rusak atau manifest.json hilang — dan saat itulah halaman
    ini paling dibutuhkan. Seluruh CSS ditanam langsung, tanpa JavaScript,
    tanpa kecuali font (yang punya fallback sistem bila jaringan gagal).

    Logo disalin mentah-mentah dari Components/Logo.jsx karena komponen
    React tidak bisa dipanggil dari Blade. Diamond spark-nya wajib #10b981.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Halaman kesalahan tidak boleh diindeks mesin pencari. --}}
        <meta name="robots" content="noindex, nofollow">

        <title>{{ $kode }} · {{ config('app.name', 'KULINARA') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link
            href="https://fonts.bunny.net/css?family=inter:400,500,600|plus-jakarta-sans:700,800&display=swap"
            rel="stylesheet"
        />

        <style>
            :root {
                --primary: #044d3c;
                --charcoal: #1a2e26;
                --muted: #676f6b;
                --warm-cream: #fbf9f4;
                --border: #e5e3dd;
                --surface: #ffffff;
            }

            *, *::before, *::after { box-sizing: border-box; }

            body {
                margin: 0;
                /* vh lebih dulu sebagai cadangan bagi peramban lama. */
                min-height: 100vh;
                min-height: 100dvh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1.5rem;
                background: var(--warm-cream);
                color: var(--charcoal);
                font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
                -webkit-font-smoothing: antialiased;
            }

            .kartu {
                width: 100%;
                max-width: 32rem;
                background: var(--surface);
                border: 1px solid var(--border);
                border-radius: 1rem;
                padding: 2.5rem 2rem;
                text-align: center;
                box-shadow: 0 1px 2px rgba(26, 46, 38, .04), 0 12px 32px rgba(26, 46, 38, .06);
            }

            .logo { display: inline-flex; align-items: center; gap: .625rem; }

            .wordmark {
                font-family: "Plus Jakarta Sans", Inter, ui-sans-serif, system-ui, sans-serif;
                font-weight: 800;
                font-size: 1.25rem;
                letter-spacing: -.02em;
                color: var(--primary);
            }

            .kode {
                margin: 1.75rem 0 0;
                font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
                font-size: 3.5rem;
                font-weight: 700;
                line-height: 1;
                letter-spacing: -.04em;
                color: var(--primary);
            }

            h1 {
                margin: .875rem 0 0;
                font-family: "Plus Jakarta Sans", Inter, ui-sans-serif, system-ui, sans-serif;
                font-size: 1.5rem;
                font-weight: 800;
                letter-spacing: -.02em;
                color: var(--charcoal);
            }

            .pesan {
                margin: .75rem auto 0;
                max-width: 27rem;
                font-size: .9375rem;
                line-height: 1.65;
                color: var(--muted);
            }

            .aksi {
                margin-top: 1.75rem;
                display: flex;
                flex-wrap: wrap;
                gap: .75rem;
                justify-content: center;
            }

            .tombol {
                display: inline-block;
                padding: .75rem 1.5rem;
                border-radius: .5rem;
                background: var(--surface);
                color: var(--primary);
                border: 1px solid var(--border);
                font-size: .875rem;
                font-weight: 600;
                text-decoration: none;
                transition: background-color 150ms ease;
            }

            .tombol:hover { background: #e8f8f2; }

            .tombol-utama {
                background: var(--primary);
                color: #fff;
                border-color: var(--primary);
            }

            .tombol-utama:hover { background: #003428; }

            .kaki {
                margin-top: 1.75rem;
                padding-top: 1.25rem;
                border-top: 1px solid var(--border);
                font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
                font-size: .6875rem;
                letter-spacing: .08em;
                text-transform: uppercase;
                color: var(--muted);
            }
        </style>
    </head>

    <body>
        <main class="kartu">
            <span class="logo">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="32" height="32" aria-hidden="true">
                    <path d="M12 2.5L13.2 4.5L15 5.5L13.2 6.5L12 8.5L10.8 6.5L9 5.5L10.8 4.5Z" fill="#10b981" stroke="#10b981" stroke-width="0.8"/>
                    <path d="M4 17C4.5 11 8 8.5 12 8.5C16 8.5 19.5 11 20 17H4Z" stroke="#044d3c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M11 7.5H13" stroke="#044d3c" stroke-width="2.2" stroke-linecap="round"/>
                    <path d="M2.5 19H21.5" stroke="#044d3c" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
                <span class="wordmark">kulinara</span>
            </span>

            <p class="kode">{{ $kode }}</p>
            <h1>{{ $judul }}</h1>
            <p class="pesan">{{ $pesan }}</p>

            <div class="aksi">
                <a class="tombol tombol-utama" href="{{ url('/') }}">Kembali ke Beranda</a>

                @if ($kode === 500)
                    {{-- Pada 500, tombol mundur kerap mengulang kegagalan yang sama.
                         Muat ulang ulang adalah tindakan yang benar. --}}
                    <a class="tombol" href="{{ url()->current() }}">Coba Muat Ulang</a>
                @elseif (request()->header('Referer'))
                    <a class="tombol" href="{{ request()->header('Referer') }}">Kembali</a>
                @endif
            </div>

            <p class="kaki">{{ config('app.name', 'KULINARA') }} · Direktori Kuliner Nusantara</p>
        </main>
    </body>
</html>
