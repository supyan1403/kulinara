import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

/**
 * Pendaftaran dalam DUA langkah, bukan satu formulir panjang.
 *
 * Halaman auth tidak boleh bisa digulir (lihat GuestLayout), jadi isi kartu
 * harus muat dalam tinggi layar. Formulir tunggal berisi nama, email, dan dua
 * kolom password membutuhkan sekitar 560px di dalam kartu — di ponsel dengan
 * bilah alamat terbuka tersisa `dvh` sekitar 560px, sehingga tombol "Daftar"
 * berisiko terpotong dan tak terjangkau.
 *
 * Dipecah dua, beban tiap langkah jatuh ke 400-an piksel dan semuanya muat
 * Bahkan di layar terpendek yang wajar. Pendaftaran selalu menghasilkan
 * Pengguna.
 */

const LANGKAH = [
    { nomor: 1, label: 'Identitas' },
    { nomor: 2, label: 'Kata sandi' },
];

// Pola email sengaja ringkas: tugasnya menahan langkah, bukan menggantikan
// validasi server yang jauh lebih teliti.
const POLA_EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

export default function Register({ statistik = [] }) {
    const { data, setData, post, processing, errors, reset, setError, clearErrors } =
        useForm({
            name: '',
            email: '',
            password: '',
            password_confirmation: '',
        });

    const [langkah, setLangkah] = useState(1);

    /**
     * Validasi langkah sebelum maju. Galat sengaja memakai `setError` milik
     * useForm supaya tampil lewat InputError yang sama dengan galat server —
     * satu jalur tampilan, bukan dua.
     */
    const maju = (e) => {
        e.preventDefault();
        clearErrors();

        if (langkah === 1) {
            const galat = {};

            if (!data.name.trim()) galat.name = 'Nama lengkap wajib diisi.';
            if (!data.email.trim()) galat.email = 'Email wajib diisi.';
            else if (!POLA_EMAIL.test(data.email.trim()))
                galat.email = 'Format email belum benar, contoh: nama@contoh.com';

            if (Object.keys(galat).length > 0) {
                setError(galat);

                return;
            }

            setLangkah(2);

            return;
        }

        // Langkah 2 adalah langkah terakhir — setelah validasi lolos,
        // langsung kirim pendaftaran.
        if (data.password.length < 8) {
            setError({ password: 'Password minimal 8 karakter.' });

            return;
        }

        if (data.password_confirmation !== data.password) {
            setError({ password_confirmation: 'Konfirmasi password tidak cocok.' });

            return;
        }

        submit(e);
    };

    const mundur = () => {
        clearErrors();
        setLangkah((n) => Math.max(1, n - 1));
    };

    const submit = (e) => {
        e.preventDefault();

        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
            // Galat server menentukan langkah mana yang harus dibuka kembali,
            // supaya pengguna tidak pernah menebak di mana masalahnya.
            onError: (galat) => {
                if (galat.name || galat.email) setLangkah(1);
                else if (galat.password || galat.password_confirmation) setLangkah(2);
            },
        });
    };

    return (
        <GuestLayout statistik={statistik}>
            <Head title="Daftar" />

            <p className="font-mono text-xs uppercase tracking-[0.2em] text-sage-dark">
                Buat akun baru
            </p>

            <h1 className="mt-3 text-2xl font-bold tracking-tight text-charcoal sm:text-3xl">
                {langkah === 1 && 'Siapa Anda?'}
                {langkah === 2 && 'Amankan akun Anda'}
            </h1>

            {langkah === 1 && (
                <p className="mt-2 font-body text-sm leading-relaxed text-muted">
                    Satu akun untuk menyimpan resep, memantau gizi, dan memasak
                    lebih terarah.
                </p>
            )}

            {/*
                Indikator kemajuan: dua segmen, bukan titik. Tiap segmen
                mewakili satu langkah yang benar-benar berisi, jadi panjangnya
                boleh dibaca sebagai "berapa banyak yang tersisa".
            */}
            <div className="mt-5" aria-hidden="true">
                <div className="flex gap-1.5">
                    {LANGKAH.map((l) => (
                        <span
                            key={l.nomor}
                            className={`h-1 flex-1 rounded-full transition-colors duration-300 ${
                                l.nomor <= langkah ? 'bg-primary' : 'bg-border'
                            }`}
                        />
                    ))}
                </div>
            </div>

            <p
                role="progressbar"
                aria-valuenow={langkah}
                aria-valuemin={1}
                aria-valuemax={LANGKAH.length}
                aria-label={`Langkah ${langkah} dari ${LANGKAH.length}`}
                className="mt-2.5 font-mono text-[11px] uppercase tracking-wider text-muted"
            >
                Langkah {langkah} dari {LANGKAH.length} — {LANGKAH[langkah - 1].label}
            </p>

            {langkah === 1 && (
                <form className="mt-5" onSubmit={maju} noValidate>
                    <div>
                        <InputLabel htmlFor="name" value="Nama Lengkap" />

                        <TextInput
                            id="name"
                            name="name"
                            value={data.name}
                            className="mt-1.5 block w-full"
                            placeholder="Nama Anda"
                            autoComplete="name"
                            isFocused={true}
                            onChange={(e) => setData('name', e.target.value)}
                        />

                        <InputError message={errors.name} className="mt-2" />
                    </div>

                    <div className="mt-4">
                        <InputLabel htmlFor="email" value="Email" />

                        <TextInput
                            id="email"
                            type="email"
                            name="email"
                            value={data.email}
                            className="mt-1.5 block w-full"
                            placeholder="nama@contoh.com"
                            autoComplete="username"
                            onChange={(e) => setData('email', e.target.value)}
                        />

                        <InputError message={errors.email} className="mt-2" />
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="mt-6 inline-flex w-full items-center justify-center rounded-lg bg-primary px-8 py-3 text-sm font-semibold text-white shadow-raised transition hover:bg-primary-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-mint-spark focus-visible:ring-offset-2 focus-visible:ring-offset-surface disabled:opacity-60"
                    >
                        Lanjut
                    </button>
                </form>
            )}

            {langkah === 2 && (
                <form className="mt-5" onSubmit={maju} noValidate>
                    <div>
                        <InputLabel htmlFor="password" value="Password" />

                        <TextInput
                            id="password"
                            type="password"
                            name="password"
                            value={data.password}
                            className="mt-1.5 block w-full"
                            placeholder="••••••••"
                            autoComplete="new-password"
                            isFocused={true}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                        />

                        <InputError message={errors.password} className="mt-2" />
                    </div>

                    <div className="mt-4">
                        <InputLabel
                            htmlFor="password_confirmation"
                            value="Konfirmasi Password"
                        />

                        <TextInput
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            value={data.password_confirmation}
                            className="mt-1.5 block w-full"
                            placeholder="••••••••"
                            autoComplete="new-password"
                            onChange={(e) =>
                                setData(
                                    'password_confirmation',
                                    e.target.value,
                                )
                            }
                        />

                        <InputError
                            message={errors.password_confirmation}
                            className="mt-2"
                        />
                    </div>

                    <p className="mt-3 font-body text-xs leading-relaxed text-muted">
                        Minimal 8 karakter.
                    </p>

                    <div className="mt-6 flex gap-3">
                        <button
                            type="button"
                            onClick={mundur}
                            className="inline-flex items-center justify-center gap-2 rounded-lg border border-border bg-surface px-5 py-3 text-sm font-semibold text-charcoal transition hover:border-primary hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                className="h-4 w-4"
                                aria-hidden="true"
                            >
                                <path d="M19 12H5" />
                                <path d="M12 19l-7-7 7-7" />
                            </svg>
                            Kembali
                        </button>

                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex flex-1 items-center justify-center rounded-lg bg-primary px-8 py-3 text-sm font-semibold text-white shadow-raised transition hover:bg-primary-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-mint-spark focus-visible:ring-offset-2 focus-visible:ring-offset-surface disabled:opacity-60"
                        >
                            {processing ? 'Mendaftar...' : 'Daftar'}
                        </button>
                    </div>
                </form>
            )}

            <p className="mt-5 border-t border-border pt-4 text-center font-body text-sm text-muted">
                Sudah punya akun?{' '}
                <Link
                    href={route('login')}
                    className="font-semibold text-primary underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                >
                    Masuk
                </Link>
            </p>
        </GuestLayout>
    );
}
