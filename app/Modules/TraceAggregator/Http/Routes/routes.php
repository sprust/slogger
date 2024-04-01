<?php

use App\Modules\TraceAggregator\Http\Controllers\ServiceStatController;
use App\Modules\TraceAggregator\Http\Controllers\TraceContentController;
use App\Modules\TraceAggregator\Http\Controllers\TraceController;
use App\Modules\TraceAggregator\Http\Controllers\TraceTreeController;
use Illuminate\Support\Facades\Route;

Route::prefix('traces')
    ->as('traces.')
    ->group(function () {
        Route::post('', [TraceController::class, 'index'])->name('index');

        Route::prefix('{traceId}')
            ->group(function () {
                Route::get('', [TraceController::class, 'show'])->name('show');
                Route::get('/tree', [TraceTreeController::class, 'index'])->name('tree');
            });
    });

Route::prefix('traces-content')
    ->as('traces-content.')
    ->group(function () {
        Route::post('/types', [TraceContentController::class, 'types'])->name('types');
        Route::post('/tags', [TraceContentController::class, 'tags'])->name('tags');
        Route::post('/statuses', [TraceContentController::class, 'statuses'])->name('statuses');
    });

Route::prefix('service-stats')
    ->as('service-stats.')
    ->group(function () {
        Route::post('', [ServiceStatController::class, 'index'])->name('index');
    });
