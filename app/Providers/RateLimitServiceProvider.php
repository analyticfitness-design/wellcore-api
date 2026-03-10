<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RateLimitServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap rate limiter definitions for the application.
     *
     * Named limiters are referenced by middleware like throttle:login
     * and throttle:api. The array cache driver is used in tests so
     * no Redis connection is required during the test suite.
     */
    public function boot(): void
    {
        $this->configureRateLimiters();
    }

    private function configureRateLimiters(): void
    {
        // Login: 5 attempts per 15 minutes per IP
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinutes(15, 5)->by($request->ip());
        });

        // AI plan generation: limited by the user's subscription plan
        RateLimiter::for('ai-generation', function (Request $request) {
            $limits = [
                'esencial' => 3,
                'metodo'   => 10,
                'elite'    => 30,
                'rise'     => 5,
            ];
            $plan  = $request->user()?->plan ?? 'esencial';
            $limit = $limits[$plan] ?? 3;

            return Limit::perDay($limit)->by($request->user()?->id ?? $request->ip());
        });

        // General API: 200 requests/minute for authenticated users, 30 for guests
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(200)->by($request->user()->id)
                : Limit::perMinute(30)->by($request->ip());
        });
    }
}
