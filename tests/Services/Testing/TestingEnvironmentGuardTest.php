<?php

declare(strict_types=1);

namespace Tests\Services\Testing;

use Illuminate\Contracts\Config\Repository;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Modules\BaseModuleTestCase;
use Tests\Support\TestingEnvironment;

class TestingEnvironmentGuardTest extends BaseModuleTestCase
{
    #[DataProvider('dataProvider')]
    public function test(
        string $key,
        mixed $value
    ): void {
        $configuration = clone $this->appMake(Repository::class);
        $configuration->set(
            key: $key,
            value: $value,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsafe test');

        TestingEnvironment::assertIsolated(
            $configuration
        );
    }

    /** @return array<string, array{string, mixed}> */
    public static function dataProvider(): array
    {
        return [
            'production environment' => [
                'app.env',
                'production',
            ],
            'default connection override' => [
                'database.default',
                'mysql',
            ],
            'working SQL server' => [
                'database.connections.sconcur_mysql.host',
                'mysql',
            ],
            'working PDO server used by migrations' => [
                'database.connections.mysql.host',
                'mysql',
            ],
            'working Mongo server' => [
                'database.connections.mongodb.traces.host',
                'mongo',
            ],
            'working ClickHouse server' => [
                'database.connections.clickhouse.host',
                'clickhouse',
            ],
            'working database name' => [
                'database.connections.mysql.database',
                'slogger',
            ],
            'URL overriding SQL host' => [
                'database.connections.sconcur_mysql.url',
                'mysql://root@mysql/slogger',
            ],
            'socket overriding PDO host' => [
                'database.connections.mysql.unix_socket',
                '/tmp/mysql.sock',
            ],
            'working Redis server' => [
                'database.redis.default.host',
                'redis',
            ],
            'URL overriding Redis host' => [
                'database.redis.cache.url',
                'redis://redis:6379',
            ],
        ];
    }
}
