<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Unggah & hapus berkas lewat Supabase Storage REST API.
 *
 * Container Vercel bersifat read-only kecuali /tmp, sehingga berkas hasil
 * upload (avatar, sampul, foto resep) tidak durable bila ditulis ke
 * filesystem lokal. Bucket Supabase dipakai sebagai gantinya: berkasnya
 * bertahan lama dan disajikan langsung lewat URL publik.
 *
 * File LAMA yang path-nya masih relatif (images/...) tetap dilayani dari
 * public/ yang ikut ter-bake di image — tidak dimigrasi.
 */
class SupabaseStorage
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $bucket,
        private readonly string $key,
    ) {
    }

    /**
     * Ambil instance dari konfigurasi env.
     */
    public static function make(): self
    {
        $baseUrl = rtrim((string) config('supabase.url'), '/');
        $bucket = (string) config('supabase.bucket');
        $key = (string) config('supabase.service_key');

        if ($baseUrl === '' || $key === '') {
            throw new RuntimeException('SUPABASE_URL / SUPABASE_SERVICE_KEY belum diatur di env.');
        }

        return new self($baseUrl, $bucket, $key);
    }

    /**
     * Simpan berkas, kembalikan URL publik permanen yang disimpan ke DB.
     */
    public function upload(UploadedFile $berkas, string $folder): string
    {
        $ext = strtolower($berkas->getClientOriginalExtension() ?: 'bin');
        $nama = $folder.'/'.uniqid('', true).'.'.$ext;

        Http::withToken($this->key)
            ->withHeaders(['x-upsert' => 'false'])
            ->withBody($berkas->get(), $berkas->getMimeType() ?: 'application/octet-stream')
            ->put("{$this->baseUrl}/storage/v1/object/{$this->bucket}/{$nama}")
            ->throw();

        return "{$this->baseUrl}/storage/v1/object/public/{$this->bucket}/{$nama}";
    }

    /**
     * Hapus berkas dari bucket berdasarkan URL yang tersimpan di DB.
     *
     * Nilai relatif lama (images/...) diabaikan di sini — pemanggil sudah
     * menanganinya lewat File::delete(). Kegagalan hapus tidak boleh
     * menjatuhkan aksi utama pengguna.
     */
    public function delete(?string $url): void
    {
        if ($url === null || ! str_contains($url, '/storage/v1/object/')) {
            return;
        }

        $marker = '/storage/v1/object/';
        $sisa = substr($url, strpos($url, $marker) + strlen($marker));

        // Bentuk: [public/]<bucket>/<path>
        $bagian = explode('/', $sisa, 3);
        if (count($bagian) === 3 && $bagian[0] === 'public') {
            array_shift($bagian);
        }

        if (count($bagian) < 2 || $bagian[1] === '') {
            return;
        }

        Http::withToken($this->key)
            ->delete("{$this->baseUrl}/storage/v1/object/{$this->bucket}/{$bagian[1]}");
    }
}
