<?php

namespace App\Modules\Trace\Framework;

use App\Modules\Common\Framework\BaseServiceProvider;
use App\Modules\Trace\Domain\Actions\Interfaces\MakeMetricIndicatorsActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\MakeTraceTimestampPeriodsActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\MakeTraceTimestampsActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\CreateTraceManyActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\DeleteTracesByTraceIdsActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\DeleteTraceTreesByTraceIdsActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\FreshTraceTimestampsActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\FreshTraceTreeActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\StartMonitorTraceIndexesActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\StopMonitorTraceIndexesActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\UpdateTraceManyActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Queries\FindStatusesActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Queries\FindTagsActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Queries\FindTraceDetailActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Queries\FindTraceIdsActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Queries\FindTraceProfilingActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Queries\FindTracesActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Queries\FindTraceTimestampsActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Queries\FindTraceTreeActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Queries\FindTypesActionInterface;
use App\Modules\Trace\Domain\Actions\MakeMetricIndicatorsAction;
use App\Modules\Trace\Domain\Actions\MakeTraceTimestampPeriodsAction;
use App\Modules\Trace\Domain\Actions\MakeTraceTimestampsAction;
use App\Modules\Trace\Domain\Actions\Mutations\CreateTraceManyAction;
use App\Modules\Trace\Domain\Actions\Mutations\DeleteTracesByTraceIdsAction;
use App\Modules\Trace\Domain\Actions\Mutations\DeleteTraceTreesByTraceIdsAction;
use App\Modules\Trace\Domain\Actions\Mutations\FreshTraceTimestampsAction;
use App\Modules\Trace\Domain\Actions\Mutations\FreshTraceTreeAction;
use App\Modules\Trace\Domain\Actions\Mutations\StartMonitorTraceIndexesAction;
use App\Modules\Trace\Domain\Actions\Mutations\StopMonitorTraceIndexesAction;
use App\Modules\Trace\Domain\Actions\Mutations\UpdateTraceManyAction;
use App\Modules\Trace\Domain\Actions\Queries\FindStatusesAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTagsAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceDetailAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceIdsAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceProfilingAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTracesAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTimestampsAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTreeAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTypesAction;
use App\Modules\Trace\Framework\Commands\FreshTraceTimestampsCommand;
use App\Modules\Trace\Framework\Commands\FreshTraceTreesCommand;
use App\Modules\Trace\Framework\Commands\StartMonitorTraceIndexesCommand;
use App\Modules\Trace\Framework\Commands\StopMonitorTraceIndexesCommand;
use App\Modules\Trace\Repositories\Interfaces\TraceContentRepositoryInterface;
use App\Modules\Trace\Repositories\Interfaces\TraceIndexRepositoryInterface;
use App\Modules\Trace\Repositories\Interfaces\TraceRepositoryInterface;
use App\Modules\Trace\Repositories\Interfaces\TraceTimestampsRepositoryInterface;
use App\Modules\Trace\Repositories\Interfaces\TraceTreeRepositoryInterface;
use App\Modules\Trace\Repositories\Services\TraceQueryBuilder;
use App\Modules\Trace\Repositories\TraceContentRepository;
use App\Modules\Trace\Repositories\TraceIndexRepository;
use App\Modules\Trace\Repositories\TraceRepository;
use App\Modules\Trace\Repositories\TraceTimestampsRepository;
use App\Modules\Trace\Repositories\TraceTreeRepository;

class TraceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(TraceQueryBuilder::class);

        parent::boot();

        $this->commands([
            FreshTraceTreesCommand::class,
            FreshTraceTimestampsCommand::class,
            StartMonitorTraceIndexesCommand::class,
            StopMonitorTraceIndexesCommand::class,
        ]);
    }

    protected function getContracts(): array
    {
        return [
            // repositories
            TraceRepositoryInterface::class                  => TraceRepository::class,
            TraceContentRepositoryInterface::class           => TraceContentRepository::class,
            TraceTreeRepositoryInterface::class              => TraceTreeRepository::class,
            TraceTimestampsRepositoryInterface::class        => TraceTimestampsRepository::class,
            TraceIndexRepositoryInterface::class             => TraceIndexRepository::class,
            // actions
            MakeMetricIndicatorsActionInterface::class       => MakeMetricIndicatorsAction::class,
            MakeTraceTimestampPeriodsActionInterface::class  => MakeTraceTimestampPeriodsAction::class,
            MakeTraceTimestampsActionInterface::class        => MakeTraceTimestampsAction::class,
            // actions.mutations
            CreateTraceManyActionInterface::class            => CreateTraceManyAction::class,
            DeleteTracesByTraceIdsActionInterface::class     => DeleteTracesByTraceIdsAction::class,
            DeleteTraceTreesByTraceIdsActionInterface::class => DeleteTraceTreesByTraceIdsAction::class,
            FreshTraceTimestampsActionInterface::class       => FreshTraceTimestampsAction::class,
            FreshTraceTreeActionInterface::class             => FreshTraceTreeAction::class,
            UpdateTraceManyActionInterface::class            => UpdateTraceManyAction::class,
            StartMonitorTraceIndexesActionInterface::class   => StartMonitorTraceIndexesAction::class,
            StopMonitorTraceIndexesActionInterface::class    => StopMonitorTraceIndexesAction::class,
            // actions.queries
            FindStatusesActionInterface::class               => FindStatusesAction::class,
            FindTagsActionInterface::class                   => FindTagsAction::class,
            FindTraceDetailActionInterface::class            => FindTraceDetailAction::class,
            FindTraceIdsActionInterface::class               => FindTraceIdsAction::class,
            FindTraceProfilingActionInterface::class         => FindTraceProfilingAction::class,
            FindTracesActionInterface::class                 => FindTracesAction::class,
            FindTraceTimestampsActionInterface::class        => FindTraceTimestampsAction::class,
            FindTraceTreeActionInterface::class              => FindTraceTreeAction::class,
            FindTypesActionInterface::class                  => FindTypesAction::class,
        ];
    }
}
