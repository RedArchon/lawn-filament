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
        // Only register the PropertyObserver when not seeding
        if (!app()->runningInConsole() || app()->runningUnitTests()) {
            \App\Models\Property::observe(\App\Observers\PropertyObserver::class);
        }
    }
}
