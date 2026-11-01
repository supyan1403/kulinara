import PrimaryButton from '@/Components/PrimaryButton';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function VerifyEmail({ status }) {
    const { post, processing } = useForm({});

    const submit = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <GuestLayout>
            <Head title="Verifikasi Email" />

            <h1 className="text-2xl font-bold tracking-tight text-charcoal">
                Verifikasi email Anda
            </h1>
            <div className="mt-2 font-body text-sm leading-relaxed text-muted">
                Sebelum mulai, mohon verifikasi alamat email Anda dengan
                mengeklik tautan yang baru saja kami kirim. Jika belum menerima
                email, kami dengan senang hati mengirimkannya ulang.
            </div>

            {status === 'verification-link-sent' && (
                <div className="mt-4 rounded-lg bg-mint-light px-3 py-2 text-sm font-medium text-sage-dark ring-1 ring-inset ring-sage-dark/20">
                    Tautan verifikasi baru telah dikirim ke alamat email yang
                    Anda daftarkan.
                </div>
            )}

            <form className="mt-6" onSubmit={submit}>
                <div className="flex items-center justify-between">
                    <PrimaryButton disabled={processing}>
                        Kirim Ulang Email
                    </PrimaryButton>

                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="text-sm text-primary underline underline-offset-2 hover:text-primary-dark focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                    >
                        Keluar
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}
