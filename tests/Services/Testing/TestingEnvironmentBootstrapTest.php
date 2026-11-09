<?php

declare(strict_types=1);

namespace Tests\Services\Testing;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class TestingEnvironmentBootstrapTest extends TestCase
{
    public function test(): void
    {
        $process = new Process(
            command: [
                PHP_BINARY,
                '-r',
                <<<'PHP_CODE'
                    require 'tests/bootstrap.php';
                    $application = require 'bootstrap/app.php';
                    Tests\Support\TestingEnvironment::guard($application);
                    $application->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
                    echo json_encode([
                        $application->environment(),
                        config('database.connections.mysql.host'),
                        config('database.connections.mongodb.traces.host'),
                        config('database.connections.clickhouse.host'),
                        config('database.redis.default.host'),
                        config('database.connections.mysql.url'),
                        $application->getCachedConfigPath(),
                    ], JSON_THROW_ON_ERROR);
                    PHP_CODE,
            ],
            cwd: dirname(__DIR__, 3),
            env: [
                'APP_ENV'          => 'production',
                'APP_BASE_PATH'    => '/does-not-exist',
                'APP_CONFIG_CACHE' => '/does-not-exist/production-config.php',
                'DB_HOST'          => 'mysql',
                'DATABASE_URL'     => 'mysql://root@mysql/slogger',
                'MONGO_HOST'       => 'mongo',
                'CLICKHOUSE_HOST'  => 'clickhouse',
                'REDIS_HOST'       => 'redis',
            ],
        );
        $process->mustRun();

        self::assertSame(
            expected: [
                'testing',
                'test-mysql',
                'test-mongo',
                'test-clickhouse',
                'test-redis',
                null,
                dirname(__DIR__, 3) . '/storage/framework/testing/config.php',
            ],
            actual: json_decode(
                json: $process->getOutput(),
                associative: true,
                flags: JSON_THROW_ON_ERROR,
            )
        );
    }
}
