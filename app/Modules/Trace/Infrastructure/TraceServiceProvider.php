<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure;

use App\Modules\Common\Infrastructure\BaseServiceProvider;
use App\Modules\Trace\Domain\Actions\MakeMetricIndicatorsAction;
use App\Modules\Trace\Domain\Actions\MakeTraceTimestampPeriodsAction;
use App\Modules\Trace\Domain\Actions\MakeTraceTimestampsAction;
use App\Modules\Trace\Domain\Actions\Mutations\CreateTraceAdminStoreAction;
use App\Modules\Trace\Domain\Actions\Mutations\DeleteCollectionsAction;
use App\Modules\Trace\Domain\Actions\Mutations\DeleteTraceAdminStoreAction;
use App\Modules\Trace\Domain\Actions\Mutations\DeleteTraceDynamicIndexAction;
use App\Modules\Trace\Domain\Actions\Mutations\FlushDynamicIndexesAction;
use App\Modules\Trace\Domain\Actions\Mutations\StartMonitorTraceDynamicIndexesAction;
use App\Modules\Trace\Domain\Actions\Mutations\StopMonitorTraceDynamicIndexesAction;
use App\Modules\Trace\Domain\Actions\Queries\FindStatusesAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTagsAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceAdminStoreAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceDetailAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceDynamicIndexesAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceDynamicIndexStatsAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceIdsAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceProfilingAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTracesAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceServicesAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTimestampsAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTreeAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTreeContentAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTypesAction;
use App\Modules\Trace\Domain\Services\TraceDynamicIndexInitializer;
use App\Modules\Trace\Domain\Services\TraceFieldTitlesService;
use App\Modules\Trace\Infrastructure\Commands\FlushDynamicIndexesCommand;
use App\Modules\Trace\Infrastructure\Commands\StartMonitorTraceDynamicIndexesCommand;
use App\Modules\Trace\Infrastructure\Commands\StopMonitorTraceDynamicIndexesCommand;
use App\Modules\Trace\Infrastructure\Http\Services\TraceDynamicIndexingActionService;
use App\Modules\Trace\Repositories\Services\PeriodicTraceCollectionNameService;
use App\Modules\Trace\Repositories\Services\PeriodicTraceService;
use App\Modules\Trace\Repositories\Services\TracePipelineBuilder;
use App\Modules\Trace\Repositories\TraceAdminStoreRepository;
use App\Modules\Trace\Repositories\TraceContentRepository;
use App\Modules\Trace\Repositories\TraceDynamicIndexRepository;
use App\Modules\Trace\Repositories\TraceRepository;
use App\Modules\Trace\Repositories\TraceTimestampsRepository;
use App\Modules\Trace\Repositories\TraceTreeCacheRepository;
use App\Modules\Trace\Repositories\TraceTreeRepository;
use Illuminate\Contracts\Foundation\Application;
use MongoDB\Client;
use SConcur\Features\Mongodb\Connection\Client as SconcurClient;

class TraceServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(
            PeriodicTraceService::class,
            static function (Application $app) {
                $username = config('database.connections.mongodb.tracesPeriodic.username');
                $password = config('database.connections.mongodb.tracesPeriodic.password');
                $host     = config('database.connections.mongodb.tracesPeriodic.host');
                $port     = config('database.connections.mongodb.tracesPeriodic.port');
                $database = config('database.connections.mongodb.tracesPeriodic.database');
                $options  = config('database.connections.mongodb.tracesPeriodic.options');

                $uri = "mongodb://$username:$password@$host:$port";

                $client = new Client($uri, $options, [
                    'typeMap' => [
                        'array'    => 'array',
                        'document' => 'array',
                        'root'     => 'array',
                    ],
                ]);

                return new PeriodicTraceService(
                    database: $client->selectDatabase($database),
                    sconcurDatabase: new SconcurClient($uri, socketTimeoutMs: $options['socketTimeoutMS'] ?? null)
                        ->selectDatabase($database),
                    periodicTraceCollectionNameService: $app->make(PeriodicTraceCollectionNameService::class)
                );
            }
        );

        $this->app->singleton(TraceFieldTitlesService::class);
        $this->app->singleton(TracePipelineBuilder::class);
        $this->app->singleton(TraceDynamicIndexInitializer::class);
        $this->app->singleton(TraceDynamicIndexingActionService::class);

        parent::boot();

        $this->commands([
            StartMonitorTraceDynamicIndexesCommand::class,
            StopMonitorTraceDynamicIndexesCommand::class,
            FlushDynamicIndexesCommand::class,
        ]);
    }

    protected function getContracts(): array
    {
        return [
            // repositories
            TraceRepository::class,
            TraceContentRepository::class,
            TraceTreeRepository::class,
            TraceTimestampsRepository::class,
            TraceDynamicIndexRepository::class,
            TraceAdminStoreRepository::class,
            TraceTreeCacheRepository::class,
            // actions
            MakeMetricIndicatorsAction::class,
            MakeTraceTimestampPeriodsAction::class,
            MakeTraceTimestampsAction::class,
            // actions.mutations
            StartMonitorTraceDynamicIndexesAction::class,
            StopMonitorTraceDynamicIndexesAction::class,
            FlushDynamicIndexesAction::class,
            DeleteTraceDynamicIndexAction::class,
            CreateTraceAdminStoreAction::class,
            DeleteTraceAdminStoreAction::class,
            DeleteCollectionsAction::class,
            // actions.queries
            FindStatusesAction::class,
            FindTagsAction::class,
            FindTraceDetailAction::class,
            FindTraceProfilingAction::class,
            FindTracesAction::class,
            FindTraceTimestampsAction::class,
            FindTraceTreeAction::class,
            FindTypesAction::class,
            FindTraceDynamicIndexesAction::class,
            FindTraceDynamicIndexStatsAction::class,
            FindTraceAdminStoreAction::class,
            FindTraceIdsAction::class,
            FindTraceServicesAction::class,
            FindTraceTreeContentAction::class,
            // services
            PeriodicTraceCollectionNameService::class
        ];
    }
}
