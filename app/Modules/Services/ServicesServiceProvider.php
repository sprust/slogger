<?php

namespace App\Modules\Services;

use App\Modules\Services\Http\RequestServiceContainer;
use Illuminate\Support\ServiceProvider;

class ServicesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(RequestServiceContainer::class);
    }
}
