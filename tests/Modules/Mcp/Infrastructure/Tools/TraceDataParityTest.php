<?php

declare (strict_types=1);

namespace Tests\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Infrastructure\Tools\GetTraceDataTool;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceDetailAction;
use App\Modules\Trace\Infrastructure\Http\Resources\Data\TraceDataResource;
use App\Services\Clickhouse\ClickhouseClient;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Services\Clickhouse\FakeClickhouseClient;
use Tests\TestCase;

class TraceDataParityTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function cases(): iterable
    {
        yield 'null' => ['null'];

        yield 'empty children' => ['[]'];

        yield 'nested values' => [
            json_encode(
                [
                    'long'   => str_repeat('payload', 10000),
                    'null'   => null,
                    'empty'  => [],
                    'bool'   => false,
                    'int'    => 42,
                    'float'  => 1.25,
                    'nested' => [
                        'child' => 'value',
                    ],
                ],
                JSON_THROW_ON_ERROR,
            ),
        ];
    }

    #[DataProvider('cases')]
    public function test(
        string $rawData
    ): void {
        $row = [
            'sid'    => 0,
            'tid'    => 'trace-parity',
            'ptid'   => '',
            'tp'     => 'request',
            'st'     => 'success',
            'tgs'    => [],
            'dt_raw' => $rawData,
            'dur'    => 0.2,
            'mem'    => null,
            'cpu'    => null,
            'pid'    => '4321',
            'lat'    => '2026-09-29 10:00:00.123456',
            'cat'    => '2026-09-29 10:00:01.000000',
            'uat'    => '2026-09-29 10:00:02.000000',
        ];
        $fakeClickhouseClient = new FakeClickhouseClient(
            answers: [
                [$row],
                [$row],
            ],
        );

        $this->app->instance(
            abstract: ClickhouseClient::class,
            instance: $fakeClickhouseClient,
        );

        $trace = $this->app->make(FindTraceDetailAction::class)->handle('trace-parity');
        $http  = json_decode(
            json_encode(new TraceDataResource($trace->data), JSON_THROW_ON_ERROR),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $result = $this->app
            ->make(GetTraceDataTool::class)
            ->call(
                new McpToolArguments([
                    'trace_id' => 'trace-parity',
                ])
            );

        $this->assertFalse(
            $result->truncate
        );

        $this->assertFalse(
            $result->isError
        );

        $this->assertSame(
            $http,
            $result->data['data']
        );

        $this->assertSame(
            [
                'key',
                'value',
                'children',
                'filter_key',
                'can_be_filtered',
            ],
            array_keys($result->data['data'])
        );

        $this->assertCount(
            2,
            $fakeClickhouseClient->selects
        );
    }
}
