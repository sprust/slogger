<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Infrastructure;

use App\Modules\Common\Infrastructure\BaseServiceProvider;
use App\Modules\Dashboard\Domain\Actions\FindDatabaseStatCacheAction;
use App\Modules\Dashboard\Domain\Actions\RefreshDatabaseStatCacheAction;
use App\Modules\Dashboard\Repositories\DatabaseStatCacheRepository;
use App\Modules\Dashboard\Repositories\DatabaseStatRepository;

class DashboardProvider extends BaseServiceProvider
{
    protected function getContracts(): array
    {
        return [
            // repositories
            DatabaseStatRepository::class,
            DatabaseStatCacheRepository::class,
            // actions
            FindDatabaseStatCacheAction::class,
            RefreshDatabaseStatCacheAction::class,
        ];
    }
}
