<?php

namespace App\Modules\Dashboard\Framework;

use App\Modules\Common\Framework\BaseServiceProvider;
use App\Modules\Dashboard\Domain\Actions\CacheServiceStatAction;
use App\Modules\Dashboard\Domain\Actions\FindDatabaseStatAction;
use App\Modules\Dashboard\Domain\Actions\FindServiceStatAction;
use App\Modules\Dashboard\Domain\Actions\Interfaces\CacheServiceStatActionInterface;
use App\Modules\Dashboard\Domain\Actions\Interfaces\FindDatabaseStatActionInterface;
use App\Modules\Dashboard\Domain\Actions\Interfaces\FindServiceStatActionInterface;
use App\Modules\Dashboard\Repositories\DatabaseStatRepository;
use App\Modules\Dashboard\Repositories\Interfaces\DatabaseStatRepositoryInterface;
use App\Modules\Dashboard\Repositories\Interfaces\ServiceStatRepositoryInterface;
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
