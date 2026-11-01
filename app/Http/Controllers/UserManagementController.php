<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    /**
     * Daftar user untuk Panel Pengelola.
     */
    public function index(): Response
    {
        return Inertia::render('Panel/Users', [
            'users' => User::query()
                ->orderBy('name')
                ->get()
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'role_label' => $user->role->label(),
                    'created_at' => $user->created_at?->format('d M Y'),
                ]),
            'roles' => collect(UserRole::cases())->map(fn (UserRole $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ]),
        ]);
    }

    /**
     * Ubah role user lain (hanya Pengelola).
     */
    public function updateRole(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        $user->update([
            'role' => UserRole::from($request->validated('role')),
        ]);

        return back()->with('success', "Role {$user->name} diperbarui menjadi {$user->role->label()}.");
    }
}
