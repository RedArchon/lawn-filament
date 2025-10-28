<?php

namespace App\Providers;

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
        // Only register the PropertyObserver when not running in console (including seeding and unit tests)
        if (!app()->runningInConsole()) {
            \App\Models\Property::observe(\App\Observers\PropertyObserver::class);
        }
    }
}
