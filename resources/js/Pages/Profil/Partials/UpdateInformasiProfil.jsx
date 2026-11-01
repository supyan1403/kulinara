import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import AvatarInisial from '@/Components/AvatarInisial';
import { useForm, usePage } from '@inertiajs/react';
import { forwardRef, useImperativeHandle } from 'react';
import { urlGambar } from '@/utils/gambar';

export default forwardRef(function UpdateInformasiProfil({ profil, mustVerifyEmail, status, onChange } = {}, ref) {
    const { auth } = usePage().props;
    const user = auth.user;

    const { data, setData, patch, errors, processing } = useForm({
        name: profil.name ?? '',
        username: profil.username ?? '',
        email: profil.email ?? '',
        bio: profil.bio ?? '',
        lokasi: profil.lokasi ?? '',
        website: profil.website ?? '',
    });

    const handleChange = (field) => (e) => {
        setData(field, e.target.value);
        if (onChange) onChange();
    };

    const simpan = async () => {
        await patch(route('profil.update'));
    };

    useImperativeHandle(ref, () => ({ simpan, processing }));

    return (
        <section>
            <header>
                <h2 className="font-display text-lg font-bold text-charcoal">
                    Informasi Profil
                </h2>
                <p className="mt-1 font-body text-sm text-muted">
                    Nama, username, bio, dan info kontak kamu.
                </p>
            </header>

            <form className="mt-6 space-y-5">
                {/* Nama */}
                <div>
                    <InputLabel htmlFor="name" value="Nama Lengkap" />
                    <TextInput
                        id="name"
                        className="mt-1 block w-full"
                        value={data.name}
                        onChange={handleChange('name')}
                        required
                        autoComplete="name"
                    />
                    <InputError className="mt-2" message={errors.name} />
                </div>

                {/* Username */}
                <div>
                    <InputLabel htmlFor="username" value="Username" />
                    <div className="relative mt-1">
                        <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm font-mono text-muted">@</span>
                        <input
                            id="username"
                            type="text"
                            className="block w-full rounded-lg border border-border bg-surface py-2 pl-7 pr-3 text-sm text-charcoal placeholder:text-muted shadow-sm transition focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                            value={data.username}
                            onChange={handleChange('username')}
                            autoComplete="username"
                            placeholder="nama-kamu"
                        />
                    </div>
                    <InputError className="mt-2" message={errors.username} />
                </div>

                {/* Email */}
                <div>
                    <InputLabel htmlFor="email" value="Email" />
                    <TextInput
                        id="email"
                        type="email"
                        className="mt-1 block w-full"
                        value={data.email}
                        onChange={handleChange('email')}
                        required
                        autoComplete="username"
                    />
                    <InputError className="mt-2" message={errors.email} />
                    {mustVerifyEmail && user.email_verified_at === null && (
                        <p className="mt-2 text-sm text-muted">
                            Email belum terverifikasi.{' '}
                            <a
                                href={route('verification.send')}
                                className="font-medium underline hover:text-charcoal"
                                onClick={(e) => {
                                    e.preventDefault();
                                    useForm().post(route('verification.send'));
                                }}
                            >
                                Kirim ulang verifikasi
                            </a>
                        </p>
                    )}
                </div>

                {/* Bio */}
                <div>
                    <InputLabel htmlFor="bio" value="Bio" />
                    <textarea
                        id="bio"
                        rows={3}
                        className="mt-1 block w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-charcoal placeholder:text-muted shadow-sm transition focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        value={data.bio}
                        onChange={handleChange('bio')}
                        placeholder="Ceritakan sedikit tentang dirimu..."
                        maxLength={1000}
                    />
                    <p className="mt-1 text-right text-xs text-muted">{data.bio.length}/1000</p>
                    <InputError className="mt-1" message={errors.bio} />
                </div>

                {/* Lokasi */}
                <div>
                    <InputLabel htmlFor="lokasi" value="Lokasi" />
                    <TextInput
                        id="lokasi"
                        className="mt-1 block w-full"
                        value={data.lokasi}
                        onChange={handleChange('lokasi')}
                        placeholder="Jakarta, Indonesia"
                    />
                    <InputError className="mt-2" message={errors.lokasi} />
                </div>

                {/* Website */}
                <div>
                    <InputLabel htmlFor="website" value="Website" />
                    <TextInput
                        id="website"
                        type="url"
                        className="mt-1 block w-full"
                        value={data.website}
                        onChange={handleChange('website')}
                        placeholder="https://contoh.com"
                    />
                    <InputError className="mt-2" message={errors.website} />
                </div>
            </form>
        </section>
    );
});
