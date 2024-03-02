<?php

namespace App\Modules\TraceAggregator\Http;

use App\Modules\TraceAggregator\Adapters\AuthAdapter;
use App\Modules\TraceAggregator\Http\Controllers\TraceController;
use App\Modules\TraceAggregator\Http\Controllers\TraceTreeController;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Route;

readonly class TraceRoutes
{
    public function __construct(private Application $app)
    {
    }

    public function init(): void
    {
        $authMiddleware = $this->app->make(AuthAdapter::class)
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
        Route::post('', [TraceController::class, 'index'])->name('index');
        Route::post('types', [TraceController::class, 'types'])->name('types');
        Route::post('tags', [TraceController::class, 'tags'])->name('tags');
        Route::post('statuses', [TraceController::class, 'statuses'])->name('statuses');
        Route::get('{traceId}', [TraceController::class, 'show'])->name('show');
    }

    private function initTreeRoutes(): void
    {
        Route::get('{traceId}', [TraceTreeController::class, 'tree'])->name('tree');
    }
}
