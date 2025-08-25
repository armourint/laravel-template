<?php

namespace App\Providers;

use App\Models\Alert;
use App\Observers\AlertObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    public function boot()
    {
        parent::boot();
        Alert::observe(AlertObserver::class);
    }
}
