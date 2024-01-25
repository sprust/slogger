<?php

namespace App\Modules\Services;

use App\Modules\Services\Http\RequestServiceContainer;
use App\Modules\Services\Repository\ServicesRepository;
use App\Modules\Services\Repository\ServicesRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class ServicesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(ServicesRepositoryInterface::class, ServicesRepository::class);
        $this->app->singleton(RequestServiceContainer::class);
    }
}
