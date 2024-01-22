<?php

namespace App\Providers;

use App\Modules\Projects\ProjectDatabaseContainer;
use App\Services\Mongo\MongoConnectorService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        MongoConnectorService::register($this->app);

        $this->app->singleton(ProjectDatabaseContainer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
