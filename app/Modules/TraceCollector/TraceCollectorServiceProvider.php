<?php

namespace App\Modules\TraceCollector;

use App\Modules\TraceCollector\Adapters\ServiceAdapter;
use App\Modules\TraceCollector\Http\Controllers\TraceCreateController;
use App\Modules\TraceCollector\Http\Controllers\TraceUpdateController;
use App\Modules\TraceCollector\Repository\TraceRepository;
use App\Modules\TraceCollector\Repository\TraceRepositoryInterface;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class TraceCollectorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerRepository();
        $this->registerRoutes();
    }

    private function registerRepository(): void
    {
        $this->app->singleton(TraceRepositoryInterface::class, TraceRepository::class);
    }

    private function registerRoutes(): void
    {
        $serviceAdapter = $this->app->make(ServiceAdapter::class);

        Route::prefix('traces-api')
            ->as('traces-api.')
            ->middleware([
                $serviceAdapter->getAuthMiddleware(),
            ])
            ->group(function () {
                Route::post('', TraceCreateController::class)->name('create');
                Route::patch('', TraceUpdateController::class)->name('update');
            });
    }
}
