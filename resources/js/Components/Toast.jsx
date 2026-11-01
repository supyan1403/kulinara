import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

/**
 * Notifikasi kilat (success/error) dari session Laravel.
 * Muncul sebentar lalu menghilang.
 *
 * Sukses memakai primary (otoritas kurasi) dengan centang mint-spark
 * sesuai peran warna "verifikasi" di dokumen Brand Essentials.
 */
export default function Toast() {
    const { flash } = usePage().props;
    const [message, setMessage] = useState(null);
    const [isError, setIsError] = useState(false);

    useEffect(() => {
        const next = flash?.success || flash?.error;

        if (!next) return;

        setMessage(next);
        setIsError(Boolean(flash.error));

        const timer = setTimeout(() => setMessage(null), 3000);

        return () => clearTimeout(timer);
    }, [flash]);

    if (!message) return null;

    return (
        <div className="pointer-events-none fixed inset-x-0 top-4 z-50 flex justify-center px-4">
            <div
                className={`pointer-events-auto flex items-center gap-2 rounded-full px-4 py-2.5 text-sm font-semibold shadow-overlay ring-1 ring-inset ${
                    isError
                        ? 'bg-danger text-white ring-danger/30'
                        : 'bg-primary text-white ring-mint-spark/40'
                }`}
            >
                {!isError && (
                    <svg viewBox="0 0 20 20" fill="currentColor" className="h-4 w-4 text-mint-spark" aria-hidden="true">
                        <path
                            fillRule="evenodd"
                            d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 9.7a1 1 0 1 1 1.4-1.4l3.3 3.3 6.8-6.8a1 1 0 0 1 1.4 0Z"
                            clipRule="evenodd"
                        />
                    </svg>
                )}
                {message}
            </div>
        </div>
    );
}
