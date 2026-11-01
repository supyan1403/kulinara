<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Penolakan resep WAJIB beralasan.
 *
 * Tanpa alasan, penulis hanya tahu karyanya ditolak tetapi tidak tahu apa
 * yang harus diperbaiki — dan penulis yang tidak tahu cara memperbaiki akan
 * berhenti menulis. Batas 500 karakter cukup untuk satu-dua kalimat koreksi
 * tanpa menjadi beban bagi Pengelola.
 */
class TolakResepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Pengelola;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'catatan' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'catatan.required' => 'Catatan penolakan wajib diisi agar penulis tahu apa yang harus diperbaiki.',
            'catatan.min' => 'Catatan penolakan minimal 10 karakter.',
            'catatan.max' => 'Catatan penolakan maksimal 500 karakter.',
        ];
    }
}
