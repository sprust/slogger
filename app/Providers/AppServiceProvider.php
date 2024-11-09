<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Octane\RoadRunner\ServerStateFile as RoadRunnerServerStateFile;
use RrParallel\Services\Drivers\Roadrunner\RpcFactory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->bootOctane();
    }

    private function bootOctane(): void
    {
        $this->app->bind(RoadRunnerServerStateFile::class, function () {
            return new RoadRunnerServerStateFile(
                storage_path('logs/octane-roadrunner-server-state.json')
            );
        });

        $this->app->singleton(RpcFactory::class, static function () {
            return new RpcFactory(
                host: config('octane.servers.roadrunner.rpc-host'),
                port: config('octane.servers.roadrunner.rpc-port')
            );
        });
    }
}
