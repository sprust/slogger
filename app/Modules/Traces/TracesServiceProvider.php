<?php

namespace App\Modules\Traces;

use App\Modules\Services\Adapters\ServicesHttpAdapter;
use App\Modules\Traces\Http\Controllers\TraceController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class TracesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        $servicesHttpAdapter = $this->app->make(ServicesHttpAdapter::class);

        Route::middleware($servicesHttpAdapter->getRequestMiddleware())
            ->prefix('traces-api')
            ->as('traces-api.')
            ->group(function () {
                Route::post('', [TraceController::class, 'create']);
            });
    }
}
