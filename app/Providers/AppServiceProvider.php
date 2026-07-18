<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

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
