import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Masuk" />

            <p className="font-mono text-xs uppercase tracking-[0.2em] text-sage-dark">
                Selamat datang kembali
            </p>

            <h1 className="mt-3 text-2xl font-bold tracking-tight text-charcoal sm:text-3xl">
                Masuk ke kulinara
            </h1>

            <p className="mt-2 font-body text-sm leading-relaxed text-muted">
                Lanjutkan memasak dari terakhir Anda.
            </p>

            {status && (
                <div
                    role="status"
                    className="mt-5 rounded-lg bg-mint-light px-3.5 py-2.5 font-body text-sm text-sage-dark ring-1 ring-inset ring-sage-dark/20"
                >
                    {status}
                </div>
            )}

            <form className="mt-6" onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="email" value="Email" />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1.5 block w-full"
                        placeholder="nama@contoh.com"
                        autoComplete="username"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                    />

                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password" value="Password" />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1.5 block w-full"
                        placeholder="••••••••"
                        autoComplete="current-password"
                        onChange={(e) => setData('password', e.target.value)}
                    />

                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <label className="flex cursor-pointer items-center">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) =>
                                setData('remember', e.target.checked)
                            }
                        />
                        <span className="ms-2 font-body text-sm text-muted">
                            Ingat saya
                        </span>
                    </label>

                    {canResetPassword && (
                        <Link
                            href={route('password.request')}
                            className="font-body text-sm text-primary underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                        >
                            Lupa password?
                        </Link>
                    )}
                </div>

                {/*
                    Geometri mengikuti tombol CTA di beranda (px-8 py-3
                    text-sm), bukan gaya PrimaryButton yang huruf kapital
                    rapat — tombol ini adalah tindakan utama halaman, jadi
                    ia pantas tampil seperti tombol utama di halaman lain.
                */}
                <button
                    type="submit"
                    disabled={processing}
                    className="mt-6 inline-flex w-full items-center justify-center rounded-lg bg-primary px-8 py-3 text-sm font-semibold text-white shadow-raised transition hover:bg-primary-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-mint-spark focus-visible:ring-offset-2 focus-visible:ring-offset-surface disabled:opacity-60"
                >
                    Masuk
                </button>
            </form>

            <p className="mt-6 border-t border-border pt-5 text-center font-body text-sm text-muted">
                Belum punya akun?{' '}
                <Link
                    href={route('register')}
                    className="font-semibold text-primary underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                >
                    Daftar gratis
                </Link>
            </p>
        </GuestLayout>
    );
}
