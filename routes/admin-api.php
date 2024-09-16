<?php

use App\Modules\Auth\Framework\Http\Controllers\LoginController;
use App\Modules\Auth\Framework\Http\Controllers\MeController;
use App\Modules\Auth\Framework\Http\Middlewares\AuthMiddleware;
use App\Modules\Cleaner\Framework\Http\Controllers\ProcessController;
use App\Modules\Cleaner\Framework\Http\Controllers\SettingController;
use App\Modules\Dashboard\Framework\Http\Controllers\DatabaseStatController;
use App\Modules\Dashboard\Framework\Http\Controllers\ServiceStatController;
use App\Modules\Service\Framework\Http\Controllers\ServiceController;
use App\Modules\Tools\Framework\Http\Controllers\ToolLinksController;
use App\Modules\Trace\Framework\Http\Controllers\TraceContentController;
use App\Modules\Trace\Framework\Http\Controllers\TraceController;
use App\Modules\Trace\Framework\Http\Controllers\TraceDynamicIndexController;
use App\Modules\Trace\Framework\Http\Controllers\TraceProfilingController;
use App\Modules\Trace\Framework\Http\Controllers\TraceTimestampPeriodsController;
use App\Modules\Trace\Framework\Http\Controllers\TraceTimestampsController;
use App\Modules\Trace\Framework\Http\Controllers\TraceTreeController;
use Illuminate\Support\Facades\Route;

Route::prefix('/auth')
    ->as('auth.')
    ->group(function () {
        Route::get('/me', MeController::class)->name('me');
        Route::post('/login', LoginController::class)->withoutMiddleware(AuthMiddleware::class)->name('login');
    });

Route::prefix('/dashboard')
    ->as('dashboard.')
    ->group(function () {
        Route::get('/database', [DatabaseStatController::class, 'index'])->name('index');
        Route::get('/service-stat', [ServiceStatController::class, 'index'])->name('index');
    });

Route::prefix('/tools')
    ->as('tools.')
    ->group(function () {
        Route::get('/links', ToolLinksController::class)->name('links');
    });

Route::prefix('/services')
    ->as('services.')
    ->group(function () {
        Route::get('', [ServiceController::class, 'index'])->name('index');
    });

Route::prefix('/trace-aggregator')
    ->as('trace-aggregator.')
    ->group(function () {
        Route::prefix('/traces')
            ->as('traces.')
            ->group(function () {
                Route::post('', [TraceController::class, 'index'])->name('index');

                Route::prefix('{traceId}')
                    ->group(function () {
                        Route::get('', [TraceController::class, 'show'])->name('show');
                        Route::get('/tree', [TraceTreeController::class, 'index'])->name('tree');
                        Route::post('/profiling', [TraceProfilingController::class, 'index'])->name('profiling');
                    });
            });

        Route::prefix('/traces-content')
            ->as('traces-content.')
            ->group(function () {
                Route::post('/types', [TraceContentController::class, 'types'])->name('types');
                Route::post('/tags', [TraceContentController::class, 'tags'])->name('tags');
                Route::post('/statuses', [TraceContentController::class, 'statuses'])->name('statuses');
            });

        Route::prefix('/trace-metrics')
            ->as('trace-metrics.')
            ->group(function () {
                Route::post('', [TraceTimestampsController::class, 'index'])->name('index');
                Route::get('/fields', [TraceTimestampsController::class, 'fields'])->name('fields');
            });

        Route::prefix('/trace-timestamp-periods')
            ->as('trace-timestamp-periods.')
            ->group(function () {
                Route::get('', [TraceTimestampPeriodsController::class, 'index'])->name('index');
            });

        Route::prefix('/dynamic-indexes')
            ->as('dynamic-indexes.')
            ->group(function () {
                Route::get('', [TraceDynamicIndexController::class, 'index'])->name('index');
                Route::get('/stats', [TraceDynamicIndexController::class, 'stats'])->name('stats');
                Route::delete('/{id}', [TraceDynamicIndexController::class, 'destroy'])->name('destroy');
            });
    });

Route::prefix('/trace-cleaner')
    ->as('trace-cleaner.')
    ->group(function () {
        Route::prefix('/settings')
            ->as('settings.')
            ->group(function () {
                Route::get('/', [SettingController::class, 'index'])
                    ->name('index');
                Route::post('/', [SettingController::class, 'store'])
                    ->name('store');
                Route::patch('/{settingId}', [SettingController::class, 'update'])
                    ->name('update');
                Route::delete('/{settingId}', [SettingController::class, 'destroy'])
                    ->name('destroy');
                Route::get('/{settingId}/processes', [ProcessController::class, 'index'])
                    ->name('processes');
            });
    });
