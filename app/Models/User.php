<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\StatusResep;
use App\Enums\UserRole;
use App\Models\Resep;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'role', 'avatar_path', 'foto_sampul', 'bio', 'lokasi', 'website', 'verified', 'notif_rating', 'notif_ulasan', 'notif_kurasi', 'porsi_default', 'satuan_default'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Ringkasan rating resep yang dipengaruhi pengguna ini, dikumpulkan
     * SEBELUM barisnya dihapus.
     *
     * `cascadeOnDelete` di tabel `ulasan` membuat PostgreSQL menghapus ulasan
     * milik pengguna ini langsung di basis data — tanpa memanggil model, dan
     * karena itu tanpa memicu `UlasanObserver`. Akibatnya `rata_rating` dan
     * `jumlah_ulasan` di resep yang pernah ia ulas akan tetap menghitung
     * ulasan yang sudah tidak ada, selamanya.
     *
     * Daftar ID-nya harus diambil di sini karena setelah penghapusan selesai
     * barisnya sudah tidak bisa ditanyakan lagi.
     *
     * @var array<int, int>
     */
    protected array $resepTerdampak = [];

    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            $user->resepTerdampak = Ulasan::query()
                ->where('user_id', $user->id)
                ->distinct()
                ->pluck('resep_id')
                ->all();
        });

        static::deleted(function (User $user): void {
            foreach ($user->resepTerdampak as $resepId) {
                // `fresh()` bukan hiasan: instance Resep yang mungkin sudah
                // ada di memori masih memegang `jumlah_ulasan` lama, dan
                // `updateQuietly()` pada instance itu bisa menuliskan kembali
                // angka basi. Membaca ulang dari basis data memastikan yang
                // dihitung adalah keadaan sesudah penghapusan.
                Resep::query()->whereKey($resepId)->first()?->fresh()?->sinkronkanRating();
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'notif_rating' => 'boolean',
            'notif_ulasan' => 'boolean',
            'notif_kurasi' => 'boolean',
            'porsi_default' => 'integer',
            'verified' => 'boolean',
        ];
    }

    /**
     * Punya akses ke Studio Dapur?
     */
    public function canAccessStudio(): bool
    {
        return $this->role->canAccessStudio();
    }

    /**
     * Punya akses ke Panel Pengelola?
     */
    public function canAccessPanel(): bool
    {
        return $this->role->canAccessPanel();
    }

    public function notifRating(): bool
    {
        return $this->notif_rating ?? true;
    }

    public function notifUlasan(): bool
    {
        return $this->notif_ulasan ?? true;
    }

    public function notifKurasi(): bool
    {
        return $this->notif_kurasi ?? true;
    }

    /**
     * Resep yang disimpan pengguna ini.
     *
     * Bukan resep miliknya — itu dihitung lewat `Resep::milik()`. Ini daftar
     * yang ia tandai supaya gampang ditemukan lagi.
     */
    public function simpanan(): HasMany
    {
        return $this->hasMany(Simpanan::class);
    }

    /**
     * Ulasan yang ditulis pengguna ini.
     */
    public function ulasan(): HasMany
    {
        return $this->hasMany(Ulasan::class);
    }

    /**
     * Baris "suka" yang DIBERIKAN pengguna ini — baik atas profil orang lain
     * maupun atas resep. Satu daftar dipakai untuk menandai hati mana yang
     * sudah terisi saat halaman dibuka.
     */
    public function sukaan(): HasMany
    {
        return $this->hasMany(Suka::class, 'user_id');
    }

    /**
     * Baris "suka profil" yang DITERIMA pengguna ini (orang lain menyukai
     * profilnya). Jumlahnya menjadi statistik "suka profil" di halaman profil
     * dan bahan skor resep unggulannya.
     */
    public function sukaProfil(): HasMany
    {
        return $this->hasMany(Suka::class, 'sukaable_id')
            ->where('sukaable_type', Suka::PROFIL);
    }

    /**
     * Resep unggulan (pinned) milik pengguna ini — hanya yang sudah terbit.
     */
    public function resepUnggulan(): HasMany
    {
        return $this->hasMany(Resep::class, 'user_id')
            ->where('unggulan', true)
            ->whereIn('status', [StatusResep::Terbit, StatusResep::Terverifikasi])
            ->orderBy('urutan_unggulan', 'desc')
            ->orderBy('id', 'desc');
    }

    /**
     * Seluruh resep yang pernah dibuat pengguna — urut terbaru dulu.
     *
     * Dipakai untuk statistik dan pencarian; tidak difilter oleh status,
     * supaya jumlahnya mencerminkan produktivitas penuh kreator.
     */
    // phpcs:disable
    public function reseps()
    {
        return $this->hasMany(Resep::class, 'user_id')->latest();
    }
}
