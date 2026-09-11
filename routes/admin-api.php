<?php

use App\Modules\Auth\Infrastructure\Http\Controllers\LoginController;
use App\Modules\Auth\Infrastructure\Http\Controllers\LogoutController;
use App\Modules\Auth\Infrastructure\Http\Controllers\MeController;
use App\Modules\Auth\Infrastructure\Http\Middlewares\AuthMiddleware;
use App\Modules\Cleaner\Infrastructure\Http\Controllers\ProcessController;
use App\Modules\Dashboard\Infrastructure\Http\Controllers\DatabaseStatController;
use App\Modules\Dashboard\Infrastructure\Http\Controllers\SconcurStatController;
use App\Modules\Logs\Infrastructure\Http\Controllers\LogController;
use App\Modules\Notification\Infrastructure\Http\Controllers\NotificationChannelController;
use App\Modules\Notification\Infrastructure\Http\Controllers\TelegramChannelController;
use App\Modules\Service\Infrastructure\Http\Controllers\ServiceController;
use App\Modules\Tools\Infrastructure\Http\Controllers\ToolLinksController;
use App\Modules\Trace\Infrastructure\Http\Controllers\TraceAdminStoreController;
use App\Modules\Trace\Infrastructure\Http\Controllers\TraceContentController;
use App\Modules\Trace\Infrastructure\Http\Controllers\TraceController;
use App\Modules\Trace\Infrastructure\Http\Controllers\TraceDynamicIndexController;
use App\Modules\Trace\Infrastructure\Http\Controllers\TraceProfilingController;
use App\Modules\Trace\Infrastructure\Http\Controllers\TraceTimestampPeriodsController;
use App\Modules\Trace\Infrastructure\Http\Controllers\TraceTimestampsController;
use App\Modules\Trace\Infrastructure\Http\Controllers\TraceTreeController;
use App\Modules\Trace\Infrastructure\Http\Controllers\TraceTreeStateController;
use App\Modules\Watcher\Infrastructure\Http\Controllers\Events\BufferOverflowIncidentEventController;
use App\Modules\Watcher\Infrastructure\Http\Controllers\Events\InvalidBufferGrownIncidentEventController;
use App\Modules\Watcher\Infrastructure\Http\Controllers\Events\LogErrorsIncidentEventController;
use App\Modules\Watcher\Infrastructure\Http\Controllers\Events\NoNewTracesIncidentEventController;
use App\Modules\Watcher\Infrastructure\Http\Controllers\Events\SlowTracesIncidentEventController;
use App\Modules\Watcher\Infrastructure\Http\Controllers\Events\ManyTracesIncidentEventController;
use App\Modules\Watcher\Infrastructure\Http\Controllers\BufferOverflowWatcherController;
use App\Modules\Watcher\Infrastructure\Http\Controllers\InvalidBufferGrownWatcherController;
use App\Modules\Watcher\Infrastructure\Http\Controllers\LogErrorsWatcherController;
use App\Modules\Watcher\Infrastructure\Http\Controllers\NoNewTracesWatcherController;
use App\Modules\Watcher\Infrastructure\Http\Controllers\SlowTracesWatcherController;
use App\Modules\Watcher\Infrastructure\Http\Controllers\ManyTracesWatcherController;
use App\Modules\Watcher\Infrastructure\Http\Controllers\WatcherController;
use App\Modules\Watcher\Infrastructure\Http\Controllers\WatcherIncidentController;
use Illuminate\Support\Facades\Route;

Route::prefix('/auth')
    ->as('auth.')
    ->group(function () {
        Route::get('/me', MeController::class)->name('me');
        Route::post('/login', LoginController::class)->withoutMiddleware(AuthMiddleware::class)->name('login');
        Route::post('/logout', LogoutController::class)->name('logout');
    });

Route::prefix('/dashboard')
    ->as('dashboard.')
    ->group(function () {
        Route::get('/database', [DatabaseStatController::class, 'index'])->name('index');
        Route::get('/sconcur', [SconcurStatController::class, 'index'])->name('sconcur');
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

                Route::post('/tree', [TraceTreeController::class, 'tree'])->name('tree');
                Route::post('/tree/content', [TraceTreeController::class, 'content'])->name('content');
                Route::get('/tree/processes', [TraceTreeStateController::class, 'processes'])->name('processes');
                Route::patch('/tree/processes/cancel', [TraceTreeStateController::class, 'cancelProcess'])->name('processes.cancel');
                Route::patch('/tree/processes/delete', [TraceTreeStateController::class, 'deleteProcess'])->name('processes.delete');

                Route::prefix('{traceId}')
                    ->group(function () {
                        Route::get('', [TraceController::class, 'show'])->name('show');
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

        Route::prefix('/states')
            ->as('states.')
            ->group(function () {
                Route::get('', [TraceAdminStoreController::class, 'index'])->name('index');
                Route::post('', [TraceAdminStoreController::class, 'create'])->name('create');
                Route::delete('/{id}', [TraceAdminStoreController::class, 'delete'])->name('delete');
            });
    });

Route::prefix('/trace-cleaner')
    ->as('trace-cleaner.')
    ->group(function () {
        Route::get('/processes', [ProcessController::class, 'index'])
            ->name('processes');
    });

Route::prefix('/watchers')
    ->as('watchers.')
    ->group(function () {
        Route::get('', [WatcherController::class, 'index'])->name('index');
        Route::get('/types', [WatcherController::class, 'types'])->name('types');

        // Before the {id} routes, or `incidents` is read as a watcher id.
        Route::prefix('/incidents')
            ->as('incidents.')
            ->group(function () {
                Route::get('', [WatcherIncidentController::class, 'index'])->name('index');
                // What the header's badge reads on every page, so it never has to fetch
                // the list to count it.
                Route::get('/stat', [WatcherIncidentController::class, 'stat'])->name('stat');
                Route::patch('/{id}/close', [WatcherIncidentController::class, 'close'])->name('close');

                // A route per type, because the payload follows the watcher's type: the
                // stored event does not carry it, and this is what makes the generated
                // schema say which numbers an event of this watcher holds.
                $eventTypes = [
                    'buffer-overflow'      => BufferOverflowIncidentEventController::class,
                    'invalid-buffer-grown' => InvalidBufferGrownIncidentEventController::class,
                    'no-new-traces'        => NoNewTracesIncidentEventController::class,
                    'many-traces'         => ManyTracesIncidentEventController::class,
                    'slow-traces'          => SlowTracesIncidentEventController::class,
                    'log-errors'           => LogErrorsIncidentEventController::class,
                ];

                foreach ($eventTypes as $segment => $controller) {
                    Route::get("/{id}/events/$segment", [$controller, 'index'])
                        ->name("events.$segment");
                }
            });

        // A route per type, because the body follows the type: this is what makes the
        // generated schema say which numbers a watcher of this type takes, rather than
        // offering every number any type might take.
        $types = [
            'buffer-overflow'      => BufferOverflowWatcherController::class,
            'invalid-buffer-grown' => InvalidBufferGrownWatcherController::class,
            'no-new-traces'        => NoNewTracesWatcherController::class,
            'many-traces'         => ManyTracesWatcherController::class,
            'slow-traces'          => SlowTracesWatcherController::class,
            'log-errors'           => LogErrorsWatcherController::class,
        ];

        foreach ($types as $segment => $controller) {
            Route::prefix("/$segment")
                ->as("$segment.")
                ->group(function () use ($controller) {
                    Route::post('', [$controller, 'create'])->name('create');
                    // The settings of one watcher, typed because the route names the type.
                    Route::get('/{id}', [$controller, 'show'])->name('show');
                    Route::patch('/{id}', [$controller, 'update'])->name('update');
                });
        }

        Route::delete('/{id}', [WatcherController::class, 'delete'])->name('delete');
    });

Route::prefix('/notification-channels')
    ->as('notification-channels.')
    ->group(function () {
        Route::get('', [NotificationChannelController::class, 'index'])->name('index');
        Route::get('/types', [NotificationChannelController::class, 'types'])->name('types');

        // A route per type, because the body follows the type.
        $types = [
            'telegram' => TelegramChannelController::class,
        ];

        foreach ($types as $segment => $controller) {
            Route::prefix("/$segment")
                ->as("$segment.")
                ->group(function () use ($controller) {
                    Route::post('', [$controller, 'create'])->name('create');
                    Route::get('/{id}', [$controller, 'show'])->name('show');
                    Route::patch('/{id}', [$controller, 'update'])->name('update');
                });
        }

        Route::get('/{id}/deliveries', [NotificationChannelController::class, 'deliveries'])->name('deliveries');
        Route::post('/{id}/test', [NotificationChannelController::class, 'test'])->name('test');
        Route::delete('/{id}', [NotificationChannelController::class, 'delete'])->name('delete');
    });

Route::prefix('/logs')
    ->as('logs.')
    ->group(function () {
        Route::get('/', [LogController::class, 'index'])->name('index');
    });
