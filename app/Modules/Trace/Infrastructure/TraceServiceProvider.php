<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure;

use App\Modules\Common\Infrastructure\BaseServiceProvider;
use App\Modules\Trace\Domain\Actions\MakeMetricIndicatorsAction;
use App\Modules\Trace\Domain\Actions\MakeTraceTimestampPeriodsAction;
use App\Modules\Trace\Domain\Actions\MakeTraceTimestampsAction;
use App\Modules\Trace\Domain\Actions\Mutations\CreateTraceAdminStoreAction;
use App\Modules\Trace\Domain\Actions\Mutations\BuildTraceTreeCacheAction;
use App\Modules\Trace\Domain\Actions\Mutations\CancelTraceTreeCacheStateAction;
use App\Modules\Trace\Domain\Actions\Mutations\DeletePartitionsAction;
use App\Modules\Trace\Domain\Actions\Mutations\RefreshTraceDataPathTypesAction;
use App\Modules\Trace\Domain\Actions\Mutations\OptimizePartitionsAction;
use App\Modules\Trace\Domain\Actions\Mutations\DeleteTraceTreeCacheAction;
use App\Modules\Trace\Domain\Actions\Mutations\DeleteTraceTreeCacheStateAction;
use App\Modules\Trace\Domain\Actions\Mutations\DeleteTraceAdminStoreAction;
use App\Modules\Trace\Domain\Actions\Queries\CountInvalidTraceBufferSinceAction;
use App\Modules\Trace\Domain\Actions\Queries\FindStatusesAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceBufferCountAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTagsAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceAdminStoreAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceDataRangeAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceDetailAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceProfilingAction;
use App\Modules\Trace\Domain\Actions\Queries\CompareTraceGroupsAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceGroupsAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTracesAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceServicesAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTimestampsAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTreeAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTreeStateAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTreeChildrenAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTreeFilteredAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTreeCacheStatesAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTreeContentAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTypesAction;
use App\Modules\Trace\Domain\Services\TraceFieldTitlesService;
use App\Modules\Trace\Domain\Services\TraceTreeCacheBuilderService;
use App\Modules\Trace\Repositories\Services\ClickhouseDataPathTypes;
use App\Modules\Trace\Repositories\Services\ClickhouseTraceFilterBuilder;
use App\Modules\Trace\Repositories\Services\ClickhouseTraceRowReader;
use App\Modules\Trace\Repositories\Services\TraceDataPathResolver;
use App\Modules\Trace\Repositories\TraceAdminStoreRepository;
use App\Modules\Trace\Repositories\TraceBufferRepository;
use App\Modules\Trace\Repositories\TraceContentRepository;
use App\Modules\Trace\Repositories\TraceRepository;
use App\Modules\Trace\Repositories\TraceGroupsRepository;
use App\Modules\Trace\Repositories\TraceTimestampsRepository;
use App\Modules\Trace\Repositories\TraceTreeCacheRepository;
use App\Modules\Trace\Repositories\TraceTreeCacheStateRepository;
use App\Modules\Trace\Repositories\TraceTreeRepository;

class TraceServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(TraceFieldTitlesService::class);

        parent::boot();
    }

    protected function getContracts(): array
    {
        return [
            // repositories
            TraceRepository::class,
            TraceContentRepository::class,
            TraceTreeRepository::class,
            TraceTimestampsRepository::class,
            TraceGroupsRepository::class,
            TraceAdminStoreRepository::class,
            TraceTreeCacheRepository::class,
            TraceTreeCacheStateRepository::class,
            TraceBufferRepository::class,
            // actions
            MakeMetricIndicatorsAction::class,
            MakeTraceTimestampPeriodsAction::class,
            MakeTraceTimestampsAction::class,
            // actions.mutations
            CreateTraceAdminStoreAction::class,
            DeleteTraceAdminStoreAction::class,
            DeletePartitionsAction::class,
            RefreshTraceDataPathTypesAction::class,
            OptimizePartitionsAction::class,
            BuildTraceTreeCacheAction::class,
            CancelTraceTreeCacheStateAction::class,
            DeleteTraceTreeCacheAction::class,
            DeleteTraceTreeCacheStateAction::class,
            // actions.queries
            FindTraceBufferCountAction::class,
            CountInvalidTraceBufferSinceAction::class,
            FindStatusesAction::class,
            FindTagsAction::class,
            FindTraceDetailAction::class,
            FindTraceDataRangeAction::class,
            FindTraceProfilingAction::class,
            FindTracesAction::class,
            FindTraceGroupsAction::class,
            CompareTraceGroupsAction::class,
            FindTraceTimestampsAction::class,
            FindTraceTreeAction::class,
            FindTraceTreeStateAction::class,
            FindTraceTreeChildrenAction::class,
            FindTraceTreeFilteredAction::class,
            FindTraceTreeCacheStatesAction::class,
            FindTypesAction::class,
            FindTraceAdminStoreAction::class,
            FindTraceServicesAction::class,
            FindTraceTreeContentAction::class,
            // services
            TraceTreeCacheBuilderService::class,
            ClickhouseDataPathTypes::class,
            TraceDataPathResolver::class,
            ClickhouseTraceFilterBuilder::class,
            ClickhouseTraceRowReader::class,
        ];
    }
}
