<?php

namespace App\Providers;

use App\Services\Clickhouse\ClickhouseClient;
use App\Services\Clickhouse\ClickhouseConnectionConfig;
use App\Services\Clickhouse\ClickhouseParameterFormatter;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use SLoggerLaravel\Configs\GeneralConfig;
use SLoggerLaravel\Helpers\TraceDataComplementer;
use SConcur\Features\HttpClient\HttpClient;
use SConcur\Features\HttpClient\HttpClientOptions;
use SLoggerLaravel\Processor;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ClickhouseConnectionConfig::class, static function (): ClickhouseConnectionConfig {
            $config = config('database.connections.clickhouse');

            return new ClickhouseConnectionConfig(
                host: (string) $config['host'],
                port: (int) $config['port'],
                database: (string) $config['database'],
                username: (string) $config['username'],
                password: (string) $config['password'],
                timeoutSeconds: (int) $config['timeout'],
            );
        });

        $this->app->singleton(ClickhouseClient::class, static function ($app): ClickhouseClient {
            $config      = $app->make(ClickhouseConnectionConfig::class);
            $httpFactory = new HttpFactory();

            return new ClickhouseClient(
                httpClient: new HttpClient(
                    responseFactory: $httpFactory,
                    options: new HttpClientOptions(
                        requestTimeoutMs: $config->timeoutSeconds * 1000,
                        connectTimeoutMs: 3_000,
                        responseHeaderTimeoutMs: $config->timeoutSeconds * 1000
                    )
                ),
                requestFactory: $httpFactory,
                streamFactory: $httpFactory,
                config: $config,
                parameterFormatter: new ClickhouseParameterFormatter(),
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        if ($this->app->make(GeneralConfig::class)->isEnabled()) {
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
    }
}
