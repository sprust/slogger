<?php

namespace App\Modules\Trace\Framework;

use App\Modules\Common\Framework\BaseServiceProvider;
use App\Modules\Trace\Domain\Actions\Interfaces\MakeMetricIndicatorsActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\MakeTraceTimestampPeriodsActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\MakeTraceTimestampsActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\ClearTracesActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\CreateTraceManyActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\DeleteTracesActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\FlushDynamicIndexesActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\FreshTraceTimestampsActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\StartMonitorTraceDynamicIndexesActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\StopMonitorTraceDynamicIndexesActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\UpdateTraceManyActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Queries\FindStatusesActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Queries\FindTagsActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Queries\FindTraceDetailActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Queries\FindTraceProfilingActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Queries\FindTracesActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Queries\FindTraceTimestampsActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Queries\FindTraceTreeActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Queries\FindTypesActionInterface;
use App\Modules\Trace\Domain\Actions\MakeMetricIndicatorsAction;
use App\Modules\Trace\Domain\Actions\MakeTraceTimestampPeriodsAction;
use App\Modules\Trace\Domain\Actions\MakeTraceTimestampsAction;
use App\Modules\Trace\Domain\Actions\Mutations\ClearTracesAction;
use App\Modules\Trace\Domain\Actions\Mutations\CreateTraceManyAction;
use App\Modules\Trace\Domain\Actions\Mutations\DeleteTracesAction;
use App\Modules\Trace\Domain\Actions\Mutations\FlushDynamicIndexesAction;
use App\Modules\Trace\Domain\Actions\Mutations\FreshTraceTimestampsAction;
use App\Modules\Trace\Domain\Actions\Mutations\StartMonitorTraceDynamicIndexesAction;
use App\Modules\Trace\Domain\Actions\Mutations\StopMonitorTraceDynamicIndexesAction;
use App\Modules\Trace\Domain\Actions\Mutations\UpdateTraceManyAction;
use App\Modules\Trace\Domain\Actions\Queries\FindStatusesAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTagsAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceDetailAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceProfilingAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTracesAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTimestampsAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTreeAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTypesAction;
use App\Modules\Trace\Framework\Commands\FlushDynamicIndexesCommand;
use App\Modules\Trace\Framework\Commands\FreshTraceTimestampsCommand;
use App\Modules\Trace\Framework\Commands\StartMonitorTraceDynamicIndexesCommand;
use App\Modules\Trace\Framework\Commands\StopMonitorTraceDynamicIndexesCommand;
use App\Modules\Trace\Framework\Http\Services\TraceDynamicIndexingActionService;
use App\Modules\Trace\Repositories\Interfaces\TraceContentRepositoryInterface;
use App\Modules\Trace\Repositories\Interfaces\TraceDynamicIndexRepositoryInterface;
use App\Modules\Trace\Repositories\Interfaces\TraceRepositoryInterface;
use App\Modules\Trace\Repositories\Interfaces\TraceTimestampsRepositoryInterface;
use App\Modules\Trace\Repositories\Interfaces\TraceTreeRepositoryInterface;
use App\Modules\Trace\Repositories\Services\TraceDynamicIndexInitializer;
use App\Modules\Trace\Repositories\Services\TraceQueryBuilder;
use App\Modules\Trace\Repositories\TraceContentRepository;
use App\Modules\Trace\Repositories\TraceDynamicIndexRepository;
use App\Modules\Trace\Repositories\TraceRepository;
use App\Modules\Trace\Repositories\TraceTimestampsRepository;
use App\Modules\Trace\Repositories\TraceTreeRepository;

class TraceServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(TraceQueryBuilder::class);
        $this->app->singleton(TraceDynamicIndexInitializer::class);
        $this->app->singleton(TraceDynamicIndexingActionService::class);

        parent::boot();

        $this->commands([
            FreshTraceTimestampsCommand::class,
            StartMonitorTraceDynamicIndexesCommand::class,
            StopMonitorTraceDynamicIndexesCommand::class,
            FlushDynamicIndexesCommand::class,
        ]);
    }

    protected function getContracts(): array
    {
        return [
            // repositories
            TraceRepositoryInterface::class                       => TraceRepository::class,
            TraceContentRepositoryInterface::class                => TraceContentRepository::class,
            TraceTreeRepositoryInterface::class                   => TraceTreeRepository::class,
            TraceTimestampsRepositoryInterface::class             => TraceTimestampsRepository::class,
            TraceDynamicIndexRepositoryInterface::class           => TraceDynamicIndexRepository::class,
            // actions
            MakeMetricIndicatorsActionInterface::class            => MakeMetricIndicatorsAction::class,
            MakeTraceTimestampPeriodsActionInterface::class       => MakeTraceTimestampPeriodsAction::class,
            MakeTraceTimestampsActionInterface::class             => MakeTraceTimestampsAction::class,
            // actions.mutations
            CreateTraceManyActionInterface::class                 => CreateTraceManyAction::class,
            ClearTracesActionInterface::class                     => ClearTracesAction::class,
            DeleteTracesActionInterface::class                    => DeleteTracesAction::class,
            FreshTraceTimestampsActionInterface::class            => FreshTraceTimestampsAction::class,
            UpdateTraceManyActionInterface::class                 => UpdateTraceManyAction::class,
            StartMonitorTraceDynamicIndexesActionInterface::class => StartMonitorTraceDynamicIndexesAction::class,
            StopMonitorTraceDynamicIndexesActionInterface::class  => StopMonitorTraceDynamicIndexesAction::class,
            FlushDynamicIndexesActionInterface::class             => FlushDynamicIndexesAction::class,
            // actions.queries
            FindStatusesActionInterface::class                    => FindStatusesAction::class,
            FindTagsActionInterface::class                        => FindTagsAction::class,
            FindTraceDetailActionInterface::class                 => FindTraceDetailAction::class,
            FindTraceProfilingActionInterface::class              => FindTraceProfilingAction::class,
            FindTracesActionInterface::class                      => FindTracesAction::class,
            FindTraceTimestampsActionInterface::class             => FindTraceTimestampsAction::class,
            FindTraceTreeActionInterface::class                   => FindTraceTreeAction::class,
            FindTypesActionInterface::class                       => FindTypesAction::class,
        ];
    }
}
