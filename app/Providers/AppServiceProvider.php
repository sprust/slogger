<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Laravel\Octane\RoadRunner\ServerStateFile as RoadRunnerServerStateFile;
use RrParallel\Services\Drivers\Roadrunner\RpcFactory;
use SLoggerLaravel\Helpers\TraceDataComplementer;
use SLoggerLaravel\Processor;

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
        if (config('slogger.enabled')) {
            $this->app->make(TraceDataComplementer::class)
                ->add(
                    key: '__context',
                    value: static function (Processor $processor) {
                        return $processor->handleWithoutTracing(
                            static fn() => [
                                ...Log::sharedContext(),
                                'pid' => getmypid(),
                            ]
                        );
                    }
                );
        }

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
