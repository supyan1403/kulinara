import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    return (
        <GuestLayout>
            <Head title="Lupa Password" />

            <h1 className="text-2xl font-bold tracking-tight text-charcoal">
                Lupa password?
            </h1>
            <div className="mt-2 font-body text-sm leading-relaxed text-muted">
                Tidak masalah. Masukkan email Anda, lalu kami akan mengirimkan
                tautan pengaturan ulang password.
            </div>

            {status && (
                <div className="mt-4 rounded-lg bg-mint-light px-3 py-2 text-sm font-medium text-sage-dark ring-1 ring-inset ring-sage-dark/20">
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
                        className="mt-1 block w-full"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                    />
                </div>

                <InputError message={errors.email} className="mt-2" />

                <div className="mt-6 flex items-center justify-end">
                    <PrimaryButton disabled={processing}>
                        Kirim Tautan Pengaturan Ulang
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
