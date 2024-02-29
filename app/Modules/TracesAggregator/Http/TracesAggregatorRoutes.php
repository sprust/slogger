<?php

namespace App\Modules\TracesAggregator\Http;

use App\Modules\TracesAggregator\Adapters\TracesAggregatorAuthAdapter;
use App\Modules\TracesAggregator\Http\Controllers\TraceAggregatorParentsController;
use App\Modules\TracesAggregator\Http\Controllers\TraceAggregatorTreeController;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Route;

readonly class TracesAggregatorRoutes
{
    public function __construct(private Application $app)
    {
    }

    public function init(): void
    {
        $authMiddleware = $this->app->make(TracesAggregatorAuthAdapter::class)
            ->getAuthMiddleware();

        Route::prefix('admin-api')
            ->as('admin-api.')
            ->middleware([
                $authMiddleware,
            ])
            ->group(function () {
                Route::prefix('/trace-aggregator')
                    ->as('trace-aggregator.')
                    ->group(function () {
                        Route::prefix('parents')
                            ->as('parents.')
                            ->group(function () {
                                $this->initParentRoutes();
                            });
                        Route::prefix('tree')
                            ->as('tree.')
                            ->group(function () {
                                $this->initTreeRoutes();
                            });
                    });
            });
    }

    private function initParentRoutes(): void
    {
        Route::post('', [TraceAggregatorParentsController::class, 'index'])->name('index');
        Route::post('types', [TraceAggregatorParentsController::class, 'types'])->name('types');
        Route::post('tags', [TraceAggregatorParentsController::class, 'tags'])->name('tags');
        Route::post('statuses', [TraceAggregatorParentsController::class, 'statuses'])->name('statuses');
        Route::get('{traceId}', [TraceAggregatorParentsController::class, 'show'])->name('show');
    }

    private function initTreeRoutes(): void
    {
        Route::get('{traceId}', [TraceAggregatorTreeController::class, 'tree'])->name('tree');
    }
}
