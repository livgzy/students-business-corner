<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use Illuminate\Support\Facades\URL;

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
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
        // Gate::define('access-tenant', function (User $user) {
        //     return $user->isTenant === true;
        // });

        // Gate::define('access-regular', function (User $user) {
        //     return $user->isTenant === false;
        // });

        // Gate::define('active-reservation', function (User $user) {
        //     $latestReservation = $user->reservation()->latest()->first();

        //     return $latestReservation?->tenant?->reservation_id !== null;
        // });
    }
}
