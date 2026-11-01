import RoleBadge from '@/Components/RoleBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

const HEADERS = ['Nama', 'Email', 'Role', 'Bergabung', 'Ubah Role'];

export default function PanelUsers({ users, roles }) {
    const { auth } = usePage().props;
    const [pendingId, setPendingId] = useState(null);

    const changeRole = (user, role) => {
        if (role === user.role) return;

        setPendingId(user.id);

        router.patch(
            route('panel.users.role', user.id),
            { role },
            {
                onFinish: () => setPendingId(null),
                preserveScroll: true,
            },
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h2 className="text-xl font-bold leading-tight tracking-tight text-charcoal">
                        Panel Pengelola
                    </h2>
                    <p className="mt-1 font-body text-sm text-muted">
                        Otoritas kurasi & manajemen pengguna.
                    </p>
                </div>
            }
        >
            <Head title="Panel Pengelola" />

            <div className="py-12">
                <div className="mx-auto max-w-shell sm:px-6 lg:px-8">
                    <div className="border border-border bg-surface shadow-card sm:rounded-2xl">
                        <div className="p-6">
                            <h3 className="text-lg font-bold text-charcoal">
                                Kelola Role Pengguna
                            </h3>
                            <p className="mt-1 font-body text-sm leading-relaxed text-muted">
                                Ubah peran pengguna KULINARA. Peran Anda sendiri
                                tidak bisa diubah dari sini.
                            </p>

                            <div className="mt-6 overflow-x-auto">
                                <table className="min-w-full divide-y divide-border">
                                    <thead>
                                        <tr className="bg-linen">
                                            {HEADERS.map((label) => (
                                                <th
                                                    key={label}
                                                    className="px-4 py-3 text-left font-mono text-[11px] font-semibold uppercase tracking-wider text-muted"
                                                >
                                                    {label}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>

                                    <tbody className="divide-y divide-border">
                                        {users.map((user) => {
                                            const isSelf =
                                                user.id === auth.user.id;
                                            const busy = pendingId === user.id;

                                            return (
                                                <tr
                                                    key={user.id}
                                                    className="hover:bg-warm-cream"
                                                >
                                                    <td className="px-4 py-3 text-sm font-semibold text-charcoal">
                                                        {user.name}
                                                        {isSelf && (
                                                            <span className="ms-2 font-body text-xs font-normal text-muted">
                                                                (Anda)
                                                            </span>
                                                        )}
                                                    </td>

                                                    <td className="px-4 py-3 font-body text-sm text-muted">
                                                        {user.email}
                                                    </td>

                                                    <td className="px-4 py-3">
                                                        <RoleBadge
                                                            role={user.role}
                                                            label={
                                                                user.role_label
                                                            }
                                                        />
                                                    </td>

                                                    <td className="px-4 py-3 font-mono text-sm text-muted tabular-nums">
                                                        {user.created_at}
                                                    </td>

                                                    <td className="px-4 py-3">
                                                        {isSelf ? (
                                                            <span className="font-body text-xs text-muted">
                                                                Tidak tersedia
                                                            </span>
                                                        ) : (
                                                            <select
                                                                value={
                                                                    user.role
                                                                }
                                                                disabled={busy}
                                                                onChange={(e) =>
                                                                    changeRole(
                                                                        user,
                                                                        e.target
                                                                            .value,
                                                                    )
                                                                }
                                                                className="rounded-lg border-border bg-surface text-sm text-charcoal shadow-sm focus:border-primary focus:ring-primary focus:ring-offset-warm-cream disabled:opacity-50"
                                                            >
                                                                {roles.map(
                                                                    (role) => (
                                                                        <option
                                                                            key={
                                                                                role.value
                                                                            }
                                                                            value={
                                                                                role.value
                                                                            }
                                                                        >
                                                                            {
                                                                                role.label
                                                                            }
                                                                        </option>
                                                                    ),
                                                                )}
                                                            </select>
                                                        )}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
