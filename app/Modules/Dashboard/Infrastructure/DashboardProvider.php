<?php

namespace App\Modules\Dashboard\Infrastructure;

use App\Modules\Common\Infrastructure\BaseServiceProvider;
use App\Modules\Dashboard\Contracts\Actions\FindDatabaseStatActionInterface;
use App\Modules\Dashboard\Contracts\Repositories\DatabaseStatRepositoryInterface;
use App\Modules\Dashboard\Domain\Actions\FindDatabaseStatAction;
use App\Modules\Dashboard\Repositories\DatabaseStatRepository;

class DashboardProvider extends BaseServiceProvider
{
    protected function getContracts(): array
    {
        return [
            // repositories
            DatabaseStatRepositoryInterface::class => DatabaseStatRepository::class,
            // actions
            FindDatabaseStatActionInterface::class => FindDatabaseStatAction::class,
        ];
    }
}
