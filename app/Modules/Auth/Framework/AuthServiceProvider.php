<?php

namespace App\Modules\Auth\Framework;

use App\Modules\Auth\Framework\Http\Controllers\LoginController;
use App\Modules\Auth\Framework\Http\Controllers\MeController;
use App\Modules\Auth\Framework\Http\Middlewares\AuthMiddleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

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
            ->group(function () {
                Route::prefix('/auth')
                    ->as('auth.')
                    ->group(function () {
                        Route::get('/me', MeController::class)->middleware(AuthMiddleware::class)->name('me');
                        Route::post('/login', LoginController::class)->name('login');
                    });
            });
    }
}
