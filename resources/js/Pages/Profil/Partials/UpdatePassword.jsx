import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { useForm } from '@inertiajs/react';
import { forwardRef, useImperativeHandle, useRef } from 'react';

export default forwardRef(function UpdatePassword({ onChange } = {}, ref) {
    const passwordRef = useRef(null);
    const currentPasswordRef = useRef(null);

    const { data, setData, errors, put, reset, processing } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const handleChange = (field) => (e) => {
        setData(field, e.target.value);
        if (onChange) onChange();
    };

    const simpan = async () => {
        await put(route('password.update'), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
            },
            onError: (err) => {
                if (err.password) {
                    reset('password', 'password_confirmation');
                    passwordRef.current?.focus();
                }
                if (err.current_password) {
                    reset('current_password');
                    currentPasswordRef.current?.focus();
                }
            },
        });
    };

    useImperativeHandle(ref, () => ({ simpan, processing }));

    return (
        <section>
            <header>
                <h2 className="font-display text-lg font-bold text-charcoal">Ganti Kata Sandi</h2>
                <p className="mt-1 font-body text-sm text-muted">
                    Gunakan kata sandi panjang dan unik untuk keamanan akun.
                </p>
            </header>

            <form className="mt-6 space-y-5">
                <div>
                    <InputLabel htmlFor="current_password" value="Kata Sandi Saat Ini" />
                    <TextInput
                        id="current_password"
                        ref={currentPasswordRef}
                        value={data.current_password}
                        onChange={handleChange('current_password')}
                        type="password"
                        className="mt-1 block w-full"
                        autoComplete="current-password"
                    />
                    <InputError message={errors.current_password} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="password" value="Kata Sandi Baru" />
                    <TextInput
                        id="password"
                        ref={passwordRef}
                        value={data.password}
                        onChange={handleChange('password')}
                        type="password"
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                    />
                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="password_confirmation" value="Konfirmasi Kata Sandi" />
                    <TextInput
                        id="password_confirmation"
                        value={data.password_confirmation}
                        onChange={handleChange('password_confirmation')}
                        type="password"
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                    />
                    <InputError message={errors.password_confirmation} className="mt-2" />
                </div>
            </form>
        </section>
    );
});
