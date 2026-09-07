<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure;

use App\Modules\Common\Infrastructure\BaseServiceProvider;
use App\Modules\Watcher\Domain\Actions\Mutations\CheckWatcherAction;
use App\Modules\Watcher\Domain\Actions\Mutations\CloseIncidentAction;
use App\Modules\Watcher\Domain\Actions\Mutations\CreateWatcherAction;
use App\Modules\Watcher\Domain\Actions\Mutations\DeleteOrphanWatcherTimelinesAction;
use App\Modules\Watcher\Domain\Actions\Mutations\DeleteWatcherAction;
use App\Modules\Watcher\Domain\Actions\Mutations\RegisterTriggerAction;
use App\Modules\Watcher\Domain\Actions\Mutations\TrimWatcherTimelineAction;
use App\Modules\Watcher\Domain\Actions\Mutations\UpdateWatcherAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindIncidentEventsAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindIncidentsAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindWatcherTypesAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindWatcherAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindWatchersAction;
use App\Modules\Watcher\Domain\Services\Checkers\BufferOverflowChecker;
use App\Modules\Watcher\Domain\Services\Checkers\InvalidBufferGrownChecker;
use App\Modules\Watcher\Domain\Services\Checkers\NoNewTracesChecker;
use App\Modules\Watcher\Domain\Services\Checkers\SlowTracesChecker;
use App\Modules\Watcher\Domain\Services\Checkers\TracesSpikeChecker;
use App\Modules\Watcher\Domain\Services\Types\BufferOverflowWatcherType;
use App\Modules\Watcher\Domain\Services\Types\InvalidBufferGrownWatcherType;
use App\Modules\Watcher\Domain\Services\Types\NoNewTracesWatcherType;
use App\Modules\Watcher\Domain\Services\Types\SlowTracesWatcherType;
use App\Modules\Watcher\Domain\Services\Types\TracesSpikeWatcherType;
use App\Modules\Watcher\Domain\Services\Types\WatcherTypeRegistry;
use App\Modules\Watcher\Domain\Services\WatcherFactory;
use App\Modules\Watcher\Domain\Services\WatcherMatchFactory;
use App\Modules\Watcher\Domain\Services\WatcherTimelineAnalyzer;
use App\Modules\Watcher\Repositories\Services\WatcherTimelineReader;
use App\Modules\Watcher\Repositories\WatcherIncidentEventRepository;
use App\Modules\Watcher\Repositories\WatcherIncidentRepository;
use App\Modules\Watcher\Repositories\WatcherRepository;
use App\Modules\Watcher\Repositories\WatcherTimelineRepository;

class WatcherServiceProvider extends BaseServiceProvider
{
    protected function getContracts(): array
    {
        return [
            // repository services
            WatcherTimelineReader::class,
            // repositories
            WatcherRepository::class,
            WatcherIncidentRepository::class,
            WatcherIncidentEventRepository::class,
            WatcherTimelineRepository::class,
            // domain services
            WatcherTimelineAnalyzer::class,
            BufferOverflowChecker::class,
            InvalidBufferGrownChecker::class,
            NoNewTracesChecker::class,
            TracesSpikeChecker::class,
            SlowTracesChecker::class,
            BufferOverflowWatcherType::class,
            InvalidBufferGrownWatcherType::class,
            NoNewTracesWatcherType::class,
            TracesSpikeWatcherType::class,
            SlowTracesWatcherType::class,
            WatcherTypeRegistry::class,
            WatcherMatchFactory::class,
            WatcherFactory::class,
            // actions
            FindWatchersAction::class,
            FindWatcherAction::class,
            FindWatcherTypesAction::class,
            FindIncidentsAction::class,
            FindIncidentEventsAction::class,
            CreateWatcherAction::class,
            UpdateWatcherAction::class,
            DeleteWatcherAction::class,
            CheckWatcherAction::class,
            RegisterTriggerAction::class,
            CloseIncidentAction::class,
            TrimWatcherTimelineAction::class,
            DeleteOrphanWatcherTimelinesAction::class,
        ];
    }
}
