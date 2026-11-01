import InputError from '@/Components/InputError';
import { useForm, usePage } from '@inertiajs/react';
import { forwardRef, useImperativeHandle, useState } from 'react';
import { urlGambar } from '@/utils/gambar';

export default forwardRef(function UpdateFotoSampul({ onChange } = {}, ref) {
    const { auth } = usePage().props;
    const user = auth.user;
    const [previewState, setPreviewState] = useState(null);

    const { data, setData, post, delete: destroy, errors, processing } = useForm({ cover: null });

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setData('cover', file);
            if (onChange) onChange();
            const reader = new FileReader();
            reader.onload = (evt) => {
                setPreviewState(evt.target.result);
            };
            reader.readAsDataURL(file);
        }
    };

    const simpan = async () => {
        if (!data.cover) return;
        await post(route('profile.cover.update'), {
            forceFormData: true,
        });
        setData('cover', null);
    };

    const hapus = () => destroy(route('profile.cover.destroy'));

    const preview = previewState || (data.cover ? URL.createObjectURL(data.cover) : urlGambar(user.foto_sampul));

    useImperativeHandle(ref, () => ({ simpan, processing }));

    return (
        <section>
            <header>
                <h2 className="font-display text-lg font-bold text-charcoal">Foto Sampul</h2>
                <p className="mt-1 font-body text-sm text-muted">
                    Foto latar di bagian atas halaman profilmu.
                </p>
            </header>

            <div className="mt-6 space-y-4">
                {preview ? (
                    <img
                        src={preview}
                        alt="Foto sampul"
                        className="aspect-[16/9] max-h-[320px] w-full rounded-xl object-cover"
                    />
                ) : (
                    <div className="aspect-[16/9] max-h-[320px] w-full rounded-xl bg-gradient-to-b from-muted/20 to-transparent" />
                )}

                <div className="flex items-center gap-3">
                    <label className="inline-flex cursor-pointer items-center rounded-lg border border-border bg-surface px-3 py-2 text-xs font-semibold uppercase tracking-widest text-charcoal shadow-sm transition hover:bg-linen">
                        Pilih Foto
                        <input
                            type="file"
                            className="sr-only"
                            accept="image/jpeg,image/png,image/webp"
                            onChange={handleFileChange}
                        />
                    </label>

                    {data.cover && (
                        <p className="text-xs text-muted">{data.cover.name}</p>
                    )}
                    <InputError message={errors.cover} />

                    {user.foto_sampul && (
                        <button
                            type="button"
                            onClick={hapus}
                            disabled={processing}
                            className="text-xs text-terracotta hover:underline disabled:opacity-50"
                        >
                            Hapus sampul
                        </button>
                    )}
                </div>
            </div>
        </section>
    );
});
