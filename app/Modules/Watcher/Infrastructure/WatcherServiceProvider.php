<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure;

use App\Modules\Common\Infrastructure\BaseServiceProvider;
use App\Modules\Watcher\Repositories\Services\WatcherMatchFactory;
use App\Modules\Watcher\Repositories\Services\WatcherSettingsMapper;
use App\Modules\Watcher\Repositories\WatcherIncidentEventRepository;
use App\Modules\Watcher\Repositories\WatcherIncidentRepository;
use App\Modules\Watcher\Repositories\WatcherRepository;

class WatcherServiceProvider extends BaseServiceProvider
{
    protected function getContracts(): array
    {
        return [
            // repository services
            WatcherSettingsMapper::class,
            WatcherMatchFactory::class,
            // repositories
            WatcherRepository::class,
            WatcherIncidentRepository::class,
            WatcherIncidentEventRepository::class,
        ];
    }
}
