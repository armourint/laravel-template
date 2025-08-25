<?php

namespace App\Providers;

use App\Livewire\UserManagement;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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
        //
        // Force Laravel to use the APP_URL env value for all URL generation & redirects.
        URL::forceRootUrl(config('app.url'));

        // If you’re using HTTPS in prod, you can also force the scheme:
        if (str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
        Livewire::component('user-management', UserManagement::class);
    }
}
