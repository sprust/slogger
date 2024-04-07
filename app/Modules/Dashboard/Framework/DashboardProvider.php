<?php

namespace App\Modules\Dashboard\Framework;

use App\Modules\Dashboard\Adapters\Service\ServiceAdapter;
use App\Modules\Dashboard\Domain\Actions\FindServiceStatAction;
use App\Modules\Dashboard\Repositories\DatabaseStatRepository;
use App\Modules\Dashboard\Repositories\Interfaces\DatabaseStatRepositoryInterface;
use App\Modules\Dashboard\Repositories\Interfaces\ServiceStatRepositoryInterface;
use App\Modules\Dashboard\Repositories\ServiceStatRepository;
use Illuminate\Support\ServiceProvider;

class DashboardProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(DatabaseStatRepositoryInterface::class, DatabaseStatRepository::class);
        $this->app->singleton(ServiceStatRepositoryInterface::class, ServiceStatRepository::class);

        $this->app->singleton(ServiceAdapter::class);
        $this->app->singleton(FindServiceStatAction::class);
    }
}
