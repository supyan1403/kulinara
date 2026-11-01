import InputError from '@/Components/InputError';
import { useForm, usePage } from '@inertiajs/react';
import { forwardRef, useImperativeHandle, useRef, useState } from 'react';
import { urlGambar } from '@/utils/gambar';
import AvatarInisial from '@/Components/AvatarInisial';

export default forwardRef(function UpdateFotoProfil({ onChange } = {}, ref) {
    const { auth } = usePage().props;
    const user = auth.user;
    const inputRef = useRef(null);
    const [preview, setPreview] = useState(null);

    const { data, setData, post, delete: destroy, errors, processing } = useForm({ avatar: null });

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setData('avatar', file);
            if (onChange) onChange();
            const reader = new FileReader();
            reader.onload = (evt) => {
                setPreview(evt.target.result);
            };
            reader.readAsDataURL(file);
        }
    };

    const simpan = async () => {
        if (!data.avatar) return;
        await post(route('profile.avatar.update'), {
            forceFormData: true,
        });
        setData('avatar', null);
    };

    const hapus = () => destroy(route('profile.avatar.destroy'));

    useImperativeHandle(ref, () => ({ simpan, processing }));

    return (
        <section>
            <header>
                <h2 className="font-display text-lg font-bold text-charcoal">Foto Profil</h2>
                <p className="mt-1 font-body text-sm text-muted">
                    Foto tampil di samping nama kamu di seluruh aplikasi.
                </p>
            </header>

            <div className="mt-6 flex flex-col items-center gap-5 sm:flex-row">
                {preview ? (
                    <img src={preview} alt="Preview" className="h-24 w-24 rounded-full object-cover ring-4 ring-white shadow-card" />
                ) : (
                    <AvatarInisial nama={user.name} foto={user.avatar_path} ukuran="lg" />
                )}

                <div className="flex flex-col gap-3">
                    <label className="inline-flex cursor-pointer items-center rounded-lg border border-border bg-surface px-3 py-2 text-xs font-semibold uppercase tracking-widest text-charcoal shadow-sm transition hover:bg-linen">
                        Pilih Foto
                        <input
                            ref={inputRef}
                            type="file"
                            className="sr-only"
                            accept="image/jpeg,image/png,image/webp"
                            onChange={handleFileChange}
                        />
                    </label>

                    {data.avatar && (
                        <p className="text-xs text-muted">{data.avatar.name}</p>
                    )}
                    <InputError message={errors.avatar} />

                    {user.avatar_path && (
                        <button
                            type="button"
                            onClick={hapus}
                            disabled={processing}
                            className="text-left text-xs text-terracotta hover:underline disabled:opacity-50"
                        >
                            Hapus foto
                        </button>
                    )}
                </div>
            </div>
        </section>
    );
});
