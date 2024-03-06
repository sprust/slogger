<?php

namespace App\Modules\Dashboard;

use App\Modules\Dashboard\Repositories\DatabaseRepository;
use App\Modules\Dashboard\Repositories\DatabaseRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class DashboardProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(DatabaseRepositoryInterface::class, DatabaseRepository::class);
    }
}
