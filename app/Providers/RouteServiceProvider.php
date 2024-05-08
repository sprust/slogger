<?php

namespace App\Providers;

use App\Modules\Auth\Framework\Http\Middlewares\AuthMiddleware;
use App\Modules\Service\Framework\Http\Middlewares\AuthServiceMiddleware;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use SLoggerLaravel\Middleware\SLoggerHttpMiddleware;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            Route::prefix('admin-api')
                ->as('admin-api.')
                ->middleware([
                    AuthMiddleware::class,
                    SLoggerHttpMiddleware::class
                ])
                ->group(base_path('routes/admin-api.php'));

            Route::middleware(AuthServiceMiddleware::class)
                ->prefix('traces-api')
                ->as('traces-api.')
                ->group(base_path('routes/traces-api.php'));
        });
    }
}
