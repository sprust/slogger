<?php

declare(strict_types=1);

namespace Tests\Modules\Dashboard\Repositories;

use App\Modules\Dashboard\Repositories\DatabaseStatRepository;
use App\Services\Mongo\MongoConnectionFactory;
use PHPUnit\Framework\TestCase;
use Tests\Services\Clickhouse\FakeClickhouseClient;

class DatabaseStatRepositoryTest extends TestCase
{
    public function testClickhouseTablesAreShownAsCollections(): void
    {
        $connections = $this->createMock(MongoConnectionFactory::class);
        $connections->method('connectionNames')->willReturn([]);

        $client = new FakeClickhouseClient([
            [
                ['table' => 'schema_migrations', 'rows' => 1, 'total' => 1024, 'data' => 512, 'indexes' => 0],
                ['table' => 'traces', 'rows' => 4, 'total' => 4 * 1024 * 1024, 'data' => 3 * 1024 * 1024, 'indexes' => 1024 * 1024],
            ],
            [
                ['table' => 'traces', 'name' => 'tid_bf', 'size' => 1024 * 1024],
            ],
            [
                ['value' => 2 * 1024 * 1024],
            ],
        ]);

        $stats = new DatabaseStatRepository($connections, $client)->find();

        $this->assertCount(1, $stats);

        $clickhouse = $stats[0];

        $this->assertSame('clickhouse', $clickhouse->name);
        $this->assertSame(5, $clickhouse->totalDocumentsCount);
        $this->assertSame(2.0, $clickhouse->memoryUsage);
        $this->assertSame(['schema_migrations', 'traces'], array_map(static fn($table) => $table->name, $clickhouse->collections));

        $traces = $clickhouse->collections[1];

        $this->assertSame(3.0, $traces->size);
        $this->assertSame(1.0, $traces->indexesSize);
        $this->assertSame(4.0, $traces->totalSize);
        $this->assertSame(1.0, $traces->avgObjSize);
        $this->assertSame('tid_bf', $traces->indexes[0]->name);
        $this->assertSame([], $clickhouse->collections[0]->indexes);
    }
}
