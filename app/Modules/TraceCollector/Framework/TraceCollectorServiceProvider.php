<?php

namespace App\Modules\TraceCollector\Framework;

use App\Modules\TraceCollector\Adapters\Service\ServiceAdapter;
use App\Modules\TraceCollector\Framework\Commands\FreshTraceTreesCommand;
use App\Modules\TraceCollector\Framework\Http\Controllers\TraceCreateController;
use App\Modules\TraceCollector\Framework\Http\Controllers\TraceUpdateController;
use App\Modules\TraceCollector\Repositories\Interfaces\TraceRepositoryInterface;
use App\Modules\TraceCollector\Repositories\Interfaces\TraceTreeRepositoryInterface;
use App\Modules\TraceCollector\Repositories\TraceRepository;
use App\Modules\TraceCollector\Repositories\TraceTreeRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class TraceCollectorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(ServiceAdapter::class);

        $this->registerRepository();
        $this->registerRoutes();

        $this->commands([
            FreshTraceTreesCommand::class,
        ]);
    }

    private function registerRepository(): void
    {
        $this->app->singleton(TraceRepositoryInterface::class, TraceRepository::class);
        $this->app->singleton(TraceTreeRepositoryInterface::class, TraceTreeRepository::class);
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
