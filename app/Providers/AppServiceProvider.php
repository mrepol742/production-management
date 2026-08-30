<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;

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
        /**
         * force HTTPS in production and testing environments to ensure secure communication and protect user data.
         */
        if (App::environment(['testing', 'production']) && !config('app.staging')) {
            URL::forceScheme('https');
        }

        $key = fn($request) => $request->user()?->id
            ? 'user:' . $request->user()->id
            : 'ip:' . $request->ip();

        RateLimiter::for('auth-actions', function ($request) use ($key) {
            return $request->isMethod('get')
                ? Limit::perMinute(60)->by($key($request))
                : Limit::perHour(10)->by($key($request));
        });
    }
}
