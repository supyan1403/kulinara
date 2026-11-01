import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';

export default function HapusAkun() {
    const [konfirmasi, setKonfirmasi] = useState(false);
    const passwordRef = useRef(null);

    const { data, setData, delete: destroy, processing, reset, errors } = useForm({ password: '' });

    const buka = () => {
        setKonfirmasi(true);
        setTimeout(() => passwordRef.current?.focus(), 50);
    };

    const tutup = () => {
        setKonfirmasi(false);
        reset();
    };

    const submit = (e) => {
        e.preventDefault();
        destroy(route('profil.destroy'), {
            preserveScroll: true,
            onError: () => passwordRef.current?.focus(),
            onFinish: () => reset(),
        });
    };

    return (
        <section>
            <header>
                <h2 className="font-display text-lg font-bold text-terracotta">Hapus Akun</h2>
                <p className="mt-1 font-body text-sm text-muted">
                    Setelah akun dihapus, semua data akan dihapus permanen dan tidak dapat dipulihkan.
                </p>
            </header>

            <div className="mt-6">
                {!konfirmasi ? (
                    <button
                        type="button"
                        onClick={buka}
                        className="inline-flex items-center rounded-lg border border-terracotta/30 bg-terracotta/5 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-terracotta shadow-sm transition hover:bg-terracotta/10"
                    >
                        Hapus Akun
                    </button>
                ) : (
                    <div className="rounded-xl border border-terracotta/20 bg-terracotta/5 p-5">
                        <p className="font-body text-sm font-semibold text-terracotta">
                            Kamu yakin ingin menghapus akun?
                        </p>
                        <p className="mt-1 font-body text-sm text-muted">
                            Masukkan kata sandi untuk mengonfirmasi.
                        </p>

                        <form onSubmit={submit} className="mt-4 space-y-4">
                            <div>
                                <InputLabel htmlFor="password_hapus" value="Kata Sandi" className="sr-only" />
                                <TextInput
                                    id="password_hapus"
                                    ref={passwordRef}
                                    type="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    className="block w-full max-w-xs"
                                    placeholder="Kata sandi"
                                    autoComplete="current-password"
                                />
                                <InputError message={errors.password} className="mt-2" />
                            </div>

                            <div className="flex items-center gap-3">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex items-center rounded-lg bg-terracotta px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition hover:bg-terracotta/80 disabled:opacity-50"
                                >
                                    Ya, Hapus Akun
                                </button>
                                <button
                                    type="button"
                                    onClick={tutup}
                                    className="text-sm text-muted hover:text-charcoal"
                                >
                                    Batal
                                </button>
                            </div>
                        </form>
                    </div>
                )}
            </div>
        </section>
    );
}
