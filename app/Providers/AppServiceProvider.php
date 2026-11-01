<?php

namespace App\Providers;

use App\Models\Ulasan;
use App\Models\User;
use App\Observers\UlasanObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Satu-satunya tempat kolom ringkasan rating di tabel `resep` ditulis.
        // Dipasang lewat observer, bukan dipanggil manual di controller, supaya
        // jalur tulis mana pun — termasuk seeder dan command — ikut terjaga.
        Ulasan::observe(UlasanObserver::class);

        Gate::define('access-studio', fn (User $user) => $user->role->canAccessStudio());

        Gate::define('access-panel', fn (User $user) => $user->role->canAccessPanel());

        Gate::define('manage-users', fn (User $user) => $user->role->canAccessPanel());
    }
}
