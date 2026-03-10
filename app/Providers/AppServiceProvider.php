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
        $this->app->singleton(\App\Services\GamificationService::class);
        $this->app->singleton(\App\Services\AiCoachService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set APP_URL dynamically for production deployments
        if (!app()->isLocal()) {
            $host = request()->getHost();
            if ($host && $host !== 'localhost') {
                \URL::forceScheme('https');
                \URL::forceRootUrl('https://' . $host);
            }
        }
    }
}
