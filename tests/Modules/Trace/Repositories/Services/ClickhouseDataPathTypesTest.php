<?php

declare(strict_types=1);

namespace Tests\Modules\Trace\Repositories\Services;

use App\Modules\Trace\Repositories\Services\ClickhouseDataPathTypes;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use PHPUnit\Framework\TestCase;
use Tests\Services\Clickhouse\FakeClickhouseClient;

class ClickhouseDataPathTypesTest extends TestCase
{
    public function testNoMapBeforeTheFirstRefreshAndNoQueryForIt(): void
    {
        $client = new FakeClickhouseClient();

        $this->assertSame([], new ClickhouseDataPathTypes($client, new Repository(new ArrayStore()))->arrayPaths());
        $this->assertSame([], $client->selects);
    }

    public function testRefreshKeepsTheArraysOfObjectsOnly(): void
    {
        $client = new FakeClickhouseClient([[[
            'paths' => [
                'user.id' => ['Int64'],
                'roles'   => ['Array(Nullable(String))'],
                'items'   => ['Array(JSON(max_dynamic_types=16, max_dynamic_paths=256))'],
                'a.list'  => ['String', 'Array(JSON(max_dynamic_types=16, max_dynamic_paths=256))'],
            ],
        ]]]);

        $pathTypes = new ClickhouseDataPathTypes($client, new Repository(new ArrayStore()));

        $this->assertSame(['a.list', 'items'], $pathTypes->refresh());
        $this->assertSame(['a.list', 'items'], $pathTypes->arrayPaths());
        $this->assertCount(1, $client->selects);
        $this->assertSame('trace-data-paths', $client->selects[0]['queryIdPrefix']);
    }
}
