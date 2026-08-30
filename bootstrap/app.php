<?php

use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Sentry\Laravel\Integration as SentryIntegration;
use Illuminate\Support\Facades\App;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        // Trust proxies to handle X-Forwarded-For headers from load balancers
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO,
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        if (App::environment('production')) {
            SentryIntegration::handles($exceptions);
        }

        // This simplify laravel exception logging by only logging relevant frames
        $exceptions->report(function (Throwable $e) {
            $trace = collect($e->getTrace())
                ->filter(
                    fn($frame) => isset($frame['file']) &&
                        str_contains($frame['file'], base_path('app')),
                )
                ->take(5)
                ->values()
                ->all();

            logger()->error($e->getMessage(), ['trace' => $trace]);

            return false;
        });
    })
    ->create();
