<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Infrastructure;

use App\Modules\Common\Infrastructure\BaseServiceProvider;
use App\Modules\Dashboard\Domain\Actions\FindDatabaseStatCacheAction;
use App\Modules\Dashboard\Domain\Actions\FindSconcurStatAction;
use App\Modules\Dashboard\Domain\Actions\FindTraceMetricsAction;
use App\Modules\Dashboard\Domain\Actions\RefreshDatabaseStatCacheAction;
use App\Modules\Dashboard\Domain\Services\SconcurStatClient;
use App\Modules\Dashboard\Repositories\DatabaseStatCacheRepository;
use App\Modules\Dashboard\Repositories\DatabaseStatRepository;
use App\Modules\Dashboard\Repositories\Services\TraceMetricReader;
use App\Modules\Dashboard\Repositories\TraceMetricRepository;

class DashboardProvider extends BaseServiceProvider
{
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
}
