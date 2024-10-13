<?php

namespace App\Modules\Dashboard\Infrastructure;

use App\Modules\Common\Infrastructure\BaseServiceProvider;
use App\Modules\Dashboard\Contracts\Actions\CacheServiceStatActionInterface;
use App\Modules\Dashboard\Contracts\Actions\FindDatabaseStatActionInterface;
use App\Modules\Dashboard\Contracts\Actions\FindServiceStatActionInterface;
use App\Modules\Dashboard\Contracts\Repositories\DatabaseStatRepositoryInterface;
use App\Modules\Dashboard\Contracts\Repositories\ServiceStatRepositoryInterface;
use App\Modules\Dashboard\Domain\Actions\CacheServiceStatAction;
use App\Modules\Dashboard\Domain\Actions\FindDatabaseStatAction;
use App\Modules\Dashboard\Domain\Actions\FindServiceStatAction;
use App\Modules\Dashboard\Repositories\DatabaseStatRepository;
use App\Modules\Dashboard\Repositories\ServiceStatRepository;

class DashboardProvider extends BaseServiceProvider
{
    protected function getContracts(): array
    {
        return [
            // repositories
            DatabaseStatRepositoryInterface::class => DatabaseStatRepository::class,
            ServiceStatRepositoryInterface::class  => ServiceStatRepository::class,
            // actions
            CacheServiceStatActionInterface::class => CacheServiceStatAction::class,
            FindDatabaseStatActionInterface::class => FindDatabaseStatAction::class,
            FindServiceStatActionInterface::class  => FindServiceStatAction::class,
        ];
    }
}
