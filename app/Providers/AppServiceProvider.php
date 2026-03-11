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
        // Trust X-Forwarded headers from EasyPanel reverse proxy
        if (!app()->isLocal()) {
            \Illuminate\Http\Request::setTrustedProxies(['*'], \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO);

            $proto = request()->header('x-forwarded-proto', 'https');
            $host = request()->header('x-forwarded-host') ?? request()->getHost();

            if ($host && $host !== 'localhost') {
                \URL::forceScheme($proto);
                \URL::forceRootUrl($proto . '://' . $host);
            }

        }
    }
}
