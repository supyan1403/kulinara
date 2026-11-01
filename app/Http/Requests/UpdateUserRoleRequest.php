<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRoleRequest extends FormRequest
{
    /**
     * Izinkan hanya Pengelola (Gate sudah didefinisikan di AppServiceProvider).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage-users') ?? false;
    }

    /**
     * Aturan validasi.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', 'string', 'in:'.implode(',', UserRole::values())],
        ];
    }

    /**
     * Pesan galat berbahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role.required' => 'Role wajib dipilih.',
            'role.in' => 'Role yang dipilih tidak valid.',
        ];
    }

    /**
     * Konfigurasi tambahan validator.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            $target = $this->route('user');

            if ($target && $user && $target->is($user)) {
                $validator->errors()->add('role', 'Anda tidak bisa mengubah role Anda sendiri.');
            }
        });
    }
}
