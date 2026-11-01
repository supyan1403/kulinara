import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { useState, useRef } from 'react';
import HapusAkun from './Partials/HapusAkun';
import UpdateFotoProfil from './Partials/UpdateFotoProfil';
import UpdateFotoSampul from './Partials/UpdateFotoSampul';
import UpdateInformasiProfil from './Partials/UpdateInformasiProfil';
import UpdatePassword from './Partials/UpdatePassword';

export default function Edit({ mustVerifyEmail, status, profil }) {
    const { auth } = usePage().props;
    const user = auth.user;
    const [tab, setTab] = useState('identitas');
    const [hasChanges, setHasChanges] = useState(false);
    const [showCancelModal, setShowCancelModal] = useState(false);

    const fotoProfilRef = useRef(null);
    const fotoSampulRef = useRef(null);
    const informasiRef = useRef(null);
    const passwordRef = useRef(null);

    const tabs = [
        { id: 'identitas', label: 'Identitas' },
        { id: 'keamanan', label: 'Keamanan' },
        { id: 'akun', label: 'Zona Bahaya' },
    ];

    const markChanged = () => setHasChanges(true);

    const simpanSemua = async () => {
        await Promise.all([
            fotoProfilRef.current?.simpan(),
            fotoSampulRef.current?.simpan(),
            informasiRef.current?.simpan(),
            passwordRef.current?.simpan(),
        ]);
        setHasChanges(false);
    };

    const sedangMenyimpan = [
        fotoProfilRef,
        fotoSampulRef,
        informasiRef,
        passwordRef,
    ].some((ref) => ref.current?.processing);

    const handleBack = () => {
        if (hasChanges) {
            setShowCancelModal(true);
            return;
        }
        if (user?.username) {
            router.visit(route('profil.show', user.username));
        } else {
            router.visit(route('dashboard'));
        }
    };

    const confirmCancel = () => {
        setShowCancelModal(false);
        if (user?.username) {
            router.visit(route('profil.show', user.username));
        } else {
            router.visit(route('dashboard'));
        }
    };

    return (
        <AuthenticatedLayout
            motif
            header={
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-bold leading-tight tracking-tight text-charcoal">
                            Pengaturan Profil
                        </h2>
                        <p className="mt-1 font-body text-sm text-muted">
                            Kelola identitas, keamanan, dan keberadaan akun kamu.
                        </p>
                    </div>
                    {hasChanges ? (
                        <div className="flex items-center gap-3">
                            <button
                                type="button"
                                onClick={simpanSemua}
                                disabled={sedangMenyimpan}
                                className="inline-flex shrink-0 items-center gap-2 rounded-lg border border-border bg-surface px-3 py-2 text-sm font-semibold text-charcoal transition hover:bg-linen disabled:opacity-50"
                            >
                                Simpan Perubahan
                            </button>
                            <button
                                type="button"
                                onClick={handleBack}
                                className="inline-flex shrink-0 items-center gap-2 rounded-lg border border-border bg-surface px-3 py-2 text-sm font-semibold text-charcoal transition hover:bg-linen"
                            >
                                Batal
                            </button>
                        </div>
                    ) : (
                        <button
                            type="button"
                            onClick={handleBack}
                            className="inline-flex shrink-0 items-center gap-2 rounded-lg border border-border bg-surface px-3 py-2 text-sm font-semibold text-charcoal transition hover:bg-linen"
                        >
                            Kembali ke Profil
                        </button>
                    )}
                </div>
            }
        >
            <Head title="Pengaturan Profil" />

            <div
                className="py-10 pb-28"
                onChange={markChanged}
                onInput={markChanged}
            >
                <div className="mx-auto max-w-shell px-4 sm:px-6 lg:px-8">
                    {/* Tabs */}
                    <div className="flex gap-1 border-b border-border pb-4 mb-6">
                        {tabs.map((t) => (
                            <button
                                key={t.id}
                                onClick={() => setTab(t.id)}
                                className={`-mb-px px-3 py-2 text-sm font-medium transition border-b-2 ${
                                    tab === t.id
                                        ? 'border-primary text-primary'
                                        : 'border-transparent text-muted hover:text-charcoal'
                                }`}
                            >
                                {t.label}
                            </button>
                        ))}
                    </div>

                    {/* Identitas */}
                    {tab === 'identitas' && (
                        <div className="space-y-6">
                            <div className="border border-border bg-surface p-4 shadow-card sm:rounded-2xl sm:p-8">
                                <UpdateFotoProfil ref={fotoProfilRef} onChange={() => markChanged()} />
                            </div>

                            <div className="border border-border bg-surface p-4 shadow-card sm:rounded-2xl sm:p-8">
                                <UpdateFotoSampul ref={fotoSampulRef} onChange={() => markChanged()} />
                            </div>

                            <div className="border border-border bg-surface p-4 shadow-card sm:rounded-2xl sm:p-8">
                                <UpdateInformasiProfil
                                    ref={informasiRef}
                                    profil={profil}
                                    mustVerifyEmail={mustVerifyEmail}
                                    status={status}
                                    onChange={() => markChanged()}
                                />
                            </div>
                        </div>
                    )}

                    {/* Keamanan */}
                    {tab === 'keamanan' && (
                        <div className="space-y-6">
                            <div className="border border-border bg-surface p-4 shadow-card sm:rounded-2xl sm:p-8">
                                <UpdatePassword ref={passwordRef} onChange={() => markChanged()} />
                            </div>
                        </div>
                    )}

                    {/* Zona Bahaya */}
                    {tab === 'akun' && (
                        <div className="space-y-6">
                            <div className="border border-border/50 bg-terracotta/5 p-4 shadow-card sm:rounded-2xl sm:p-8">
                                <HapusAkun />
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {showCancelModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center px-4">
                    <div
                        className="absolute inset-0 bg-charcoal/60 backdrop-blur-sm"
                        onClick={() => setShowCancelModal(false)}
                    />
                    <div className="relative w-full max-w-md rounded-2xl bg-surface p-6 shadow-card">
                        <h3 className="text-lg font-bold text-charcoal">Yakin batal?</h3>
                        <p className="mt-2 text-sm text-muted">
                            Perubahan yang belum disimpan akan hilang.
                        </p>
                        <div className="mt-6 flex justify-end gap-2">
                            <button
                                type="button"
                                onClick={() => setShowCancelModal(false)}
                                className="inline-flex items-center rounded-lg border border-border px-4 py-2 text-sm font-semibold text-charcoal transition hover:bg-linen"
                            >
                                Lanjut Edit
                            </button>
                            <button
                                type="button"
                                onClick={confirmCancel}
                                className="inline-flex items-center rounded-lg bg-terracotta px-4 py-2 text-sm font-semibold text-white transition hover:bg-terracotta/90"
                            >
                                Ya, Batalkan
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
