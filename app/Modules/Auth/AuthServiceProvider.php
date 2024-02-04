<?php

namespace App\Modules\Auth;

use App\Modules\Auth\Http\Controllers\AuthLoginController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use SLoggerLaravel\Middleware\SLoggerHttpMiddleware;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        Route::prefix('admin-api')
            ->as('admin-api.')
            ->middleware([
                SLoggerHttpMiddleware::class,
            ])
            ->group(function () {
                Route::prefix('/auth')
                    ->as('auth.')
                    ->group(function () {
                        Route::post('/login', AuthLoginController::class);
                    });
            });
    }
}
