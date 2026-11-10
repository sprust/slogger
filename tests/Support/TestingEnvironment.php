<?php

declare(strict_types=1);

namespace Tests\Support;

use Dotenv\Dotenv;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use RuntimeException;

class TestingEnvironment
{
    public static function load(): void
    {
        $root = dirname(__DIR__, 2);

        $dotenv = Dotenv::createMutable(
            paths: $root,
            names: '.env.testing',
        );
        $dotenv->load();

        $_ENV['APP_BASE_PATH']    = $root;
        $_SERVER['APP_BASE_PATH'] = $root;
    }

    public static function guard(Application $application): void
    {
        $application->beforeBootstrapping(
            bootstrapper: RegisterProviders::class,
            callback: static function (Application $application): void {
                self::assertIsolated(
                    $application->make('config')
                );
            },
        );
    }

    public static function assertIsolated(Repository $configuration): void
    {
        $expected = [
            'app.env'                                      => 'testing',
            'database.default'                             => 'sconcur_mysql',
            'database.connections.mysql.host'              => 'test-mysql',
            'database.connections.mysql.database'          => 'slogger_test',
            'database.connections.sconcur_mysql.host'      => 'test-mysql',
            'database.connections.sconcur_mysql.database'  => 'slogger_test',
            'database.connections.mongodb.traces.host'     => 'test-mongo',
            'database.connections.mongodb.traces.database' => 'traces_test',
            'database.connections.clickhouse.host'         => 'test-clickhouse',
            'database.connections.clickhouse.database'     => 'slogger_test',
            'database.redis.default.host'                  => 'test-redis',
            'database.redis.default.url'                   => null,
            'database.redis.cache.host'                    => 'test-redis',
            'database.redis.cache.url'                     => null,
        ];

        foreach ($expected as $key => $value) {
            if ($configuration->get($key) !== $value) {
                throw new RuntimeException("Unsafe test configuration: $key");
            }
        }

        foreach (['mysql', 'sconcur_mysql'] as $connection) {
            if (
                $configuration->get("database.connections.$connection.url") !== null
                || $configuration->get("database.connections.$connection.unix_socket") !== ''
            ) {
                throw new RuntimeException("Unsafe test connection override: $connection");
            }
        }
    }
}
