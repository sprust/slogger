<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Octane\RoadRunner\ServerStateFile as RoadRunnerServerStateFile;
use Laravel\Octane\Swoole\ServerStateFile as SwooleServerStateFile;

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
        $this->app->bind(RoadRunnerServerStateFile::class, function ($app) {
            return new RoadRunnerServerStateFile(
                $app['config']->get(
                    'octane.state_file',
                    storage_path('logs/octane-roadrunner-server-state.json')
                )
            );
        });

        $this->app->bind(SwooleServerStateFile::class, function ($app) {
            return new SwooleServerStateFile(
                $app['config']->get(
                    'octane.state_file',
                    storage_path('logs/octane-swoole-server-state.json')
                )
            );
        });
    }
}
