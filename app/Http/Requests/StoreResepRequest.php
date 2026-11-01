<?php

namespace App\Http\Requests;

use App\Enums\KategoriResep;
use App\Enums\StatusResep;
use App\Enums\TingkatKesulitan;
use App\Enums\UserRole;
use App\Models\Resep;
use Illuminate\Foundation\Http\FormRequest;

class StoreResepRequest extends FormRequest
{
    /**
     * Izinkan hanya peran yang punya akses Studio Dapur.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Resep::class) ?? false;
    }

    /**
     * Aturan validasi — bentuknya mengikuti kolom `jsonb` di tabel resep.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            // Identitas
            'judul' => ['required', 'string', 'min:5', 'max:120'],
            'deskripsi' => ['required', 'string', 'min:20', 'max:1000'],
            'kategori' => ['required', 'in:'.implode(',', KategoriResep::values())],
            'tingkat_kesulitan' => ['required', 'in:'.implode(',', TingkatKesulitan::values())],
            'waktu_masak_menit' => ['required', 'integer', 'between:1,9999'],
            'porsi' => ['required', 'integer', 'between:1,100'],

            // Gizi (per porsi).
            //
            // `gizi_mode` menentukan siapa yang mengisi: `otomatis` berarti
            // server menghitung dari daftar bahan lewat KalkulasiGizi (empat
            // kolom di bawah jadi opsional), `manual` berarti penulis resep
            // mengisi sendiri — jalan keluar ketika ada bahan yang tak bisa
            // dikonversi, misalnya "secukupnya".
            'gizi_mode' => ['nullable', 'in:otomatis,manual'],
            'energi_kkal' => [$this->giziWajib(), 'numeric', 'min:0'],
            'protein_g' => [$this->giziWajib(), 'numeric', 'min:0'],
            'lemak_g' => [$this->giziWajib(), 'numeric', 'min:0'],
            'karbohidrat_g' => [$this->giziWajib(), 'numeric', 'min:0'],

            // Bahan — array objek {nama, jumlah, satuan} dan, opsional,
            // `bahan_id` yang menunjuk rujukan bahan_pangan. Tanpa
            // `bahan_id` baris tetap sah sebagai "bahan custom", hanya
            // gizinya yang tidak bisa dihitung otomatis.
            'bahan' => ['required', 'array', 'min:1', 'max:50'],
            'bahan.*.nama' => ['required', 'string', 'max:80'],
            'bahan.*.jumlah' => ['required', 'numeric', 'min:0.01'],
            'bahan.*.satuan' => ['required', 'string', 'max:20'],
            'bahan.*.bahan_id' => ['nullable', 'integer', 'exists:bahan_pangan,id'],

            // Standarisasi — hasil akhir & peralatan. Semua opsional:
            // resep lama tidak kehilangan apa pun, dan standarisasi
            // bertumbuh seiring penulis mengisinya.
            'hasil_total' => ['nullable', 'numeric', 'min:0.01', 'max:999999.99'],
            'satuan_hasil' => ['nullable', 'in:gram,ml', 'required_with:hasil_total'],
            'alat' => ['nullable', 'array', 'max:20'],
            'alat.*' => ['required', 'string', 'max:60'],

            // Langkah — array string
            'langkah' => ['required', 'array', 'min:1', 'max:30'],
            'langkah.*' => ['required', 'string', 'min:5', 'max:500'],

            'status' => ['nullable', 'in:'.implode(',', $this->statusDiizinkan())],
            'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * Mode pengisian gizi yang dipilih penulis resep.
     *
     * Nilai selain `manual` dianggap `otomatis` — termasuk saat kolom tidak
     * dikirim sama sekali, sehingga resep lama (yang gizinya manual) tetap
     * terbaca sebagai manual hanya bila UI benar-benar mengirimnya.
     */
    protected function giziMode(): string
    {
        return $this->input('gizi_mode') === 'manual' ? 'manual' : 'otomatis';
    }

    /**
     * Aturan keharusan gizi menurut mode.
     *
     * Mode otomatis: kolom gizi opsional, karena server yang menghitung.
     * Mode manual: kolom gizi wajib, persis seperti perilaku lama.
     */
    protected function giziWajib(): string
    {
        return $this->giziMode() === 'manual' ? 'required' : 'nullable';
    }

    /**
     * Status yang boleh dipilih sesuai peran.
     *
     * Pengguna hanya Draft — menayangkan resep adalah keputusan Pengelola
     * lewat antrean kurasi. Hanya Pengelola yang boleh memilih status lain.
     *
     * @return array<int, string>
     */
    protected function statusDiizinkan(): array
    {
        return StatusResep::diizinkanUntuk(
            $this->user()?->role ?? UserRole::Pengguna,
        );
    }

    /**
     * Pesan galat berbahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'judul.required' => 'Judul resep wajib diisi.',
            'judul.min' => 'Judul resep minimal 5 karakter.',
            'judul.max' => 'Judul resep maksimal 120 karakter.',
            'deskripsi.required' => 'Deskripsi resep wajib diisi.',
            'deskripsi.min' => 'Deskripsi minimal 20 karakter.',
            'kategori.required' => 'Kategori resep wajib dipilih.',
            'kategori.in' => 'Kategori resep yang dipilih tidak valid.',
            'tingkat_kesulitan.required' => 'Tingkat kesulitan wajib dipilih.',
            'tingkat_kesulitan.in' => 'Tingkat kesulitan yang dipilih tidak valid.',
            'waktu_masak_menit.required' => 'Waktu masak wajib diisi.',
            'waktu_masak_menit.between' => 'Waktu masak harus antara 1 sampai 9999 menit.',
            'porsi.required' => 'Jumlah porsi wajib diisi.',
            'porsi.between' => 'Jumlah porsi harus antara 1 sampai 100.',
            'energi_kkal.required' => 'Nilai energi wajib diisi saat mode manual.',
            'energi_kkal.min' => 'Nilai energi tidak boleh negatif.',
            'protein_g.required' => 'Nilai protein wajib diisi saat mode manual.',
            'protein_g.min' => 'Nilai protein tidak boleh negatif.',
            'lemak_g.required' => 'Nilai lemak wajib diisi saat mode manual.',
            'lemak_g.min' => 'Nilai lemak tidak boleh negatif.',
            'karbohidrat_g.required' => 'Nilai karbohidrat wajib diisi saat mode manual.',
            'karbohidrat_g.min' => 'Nilai karbohidrat tidak boleh negatif.',
            'bahan.*.bahan_id.exists' => 'Bahan yang dipilih tidak ada di rujukan bahan pangan.',
            'hasil_total.min' => 'Hasil akhir harus lebih dari 0.',
            'satuan_hasil.required_with' => 'Satuan hasil wajib dipilih bila hasil akhir diisi.',
            'satuan_hasil.in' => 'Satuan hasil harus gram atau mililiter.',
            'alat.max' => 'Maksimal 20 peralatan.',
            'bahan.required' => 'Minimal satu bahan wajib diisi.',
            'bahan.min' => 'Minimal satu bahan wajib diisi.',
            'bahan.*.nama.required' => 'Nama bahan wajib diisi.',
            'bahan.*.jumlah.required' => 'Jumlah bahan wajib diisi.',
            'bahan.*.jumlah.min' => 'Jumlah bahan harus lebih dari 0.',
            'bahan.*.satuan.required' => 'Satuan bahan wajib diisi.',
            'langkah.required' => 'Minimal satu langkah memasak wajib diisi.',
            'langkah.min' => 'Minimal satu langkah memasak wajib diisi.',
            'langkah.*.required' => 'Setiap langkah memasak wajib diisi.',
            'langkah.*.min' => 'Setiap langkah memasak minimal 5 karakter.',
            'status.in' => 'Status tidak valid. Pengguna hanya boleh memilih Draft.',
            'gambar.image' => 'Berkas harus berupa gambar.',
            'gambar.mimes' => 'Format gambar harus JPG, JPEG, PNG, atau WebP.',
            'gambar.max' => 'Ukuran gambar maksimal 2 MB.',
        ];
    }
}
