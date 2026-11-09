<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Infrastructure;

use App\Modules\Dashboard\Infrastructure\Exceptions\DashboardExceptionManager;
use App\Modules\Dashboard\Infrastructure\Jobs\RefreshDatabaseStatCacheJob;
use App\Modules\Common\Infrastructure\Providers\BaseServiceProvider;
use App\Modules\Dashboard\Domain\Actions\FindDatabaseStatCacheAction;
use App\Modules\Dashboard\Domain\Actions\FindSconcurStatAction;
use App\Modules\Dashboard\Domain\Actions\FindTraceMetricsAction;
use App\Modules\Dashboard\Domain\Actions\RefreshDatabaseStatCacheAction;
use App\Modules\Dashboard\Domain\Services\SconcurStatClient;
use App\Modules\Dashboard\Repositories\DatabaseStatCacheRepository;
use App\Modules\Dashboard\Repositories\DatabaseStatRepository;
use App\Modules\Dashboard\Repositories\Services\TraceMetricReader;
use App\Modules\Dashboard\Repositories\TraceMetricRepository;
use App\Modules\Common\Domain\Services\DispatcherInterface;
use Illuminate\Console\Scheduling\Schedule;

class DashboardProvider extends BaseServiceProvider
{
    public static function setSchedule(Schedule $schedule): void
    {
        $schedule
            ->call(
                callback: static function (DispatcherInterface $dispatcher): void {
                    $dispatcher->dispatch(new RefreshDatabaseStatCacheJob());
                },
            )
            ->name(RefreshDatabaseStatCacheJob::class)
            ->everyFiveMinutes();
    }

    protected function getJobs(): array
    {
        return [
            RefreshDatabaseStatCacheJob::class,
        ];
    }

    protected function getContracts(): array
    {
        return [
            // repository services
            TraceMetricReader::class,
            // repositories
            DatabaseStatRepository::class,
            DatabaseStatCacheRepository::class,
            TraceMetricRepository::class,
            // services
            SconcurStatClient::class,
            // actions
            FindDatabaseStatCacheAction::class,
            RefreshDatabaseStatCacheAction::class,
            FindSconcurStatAction::class,
            FindTraceMetricsAction::class,
        ];
    }

    protected function getExceptionManagerClass(): ?string
    {
        return DashboardExceptionManager::class;
    }

    protected function getCommands(): array
    {
        return [];
    }

    protected function getListeners(): array
    {
        return [];
    }
}
