<?php

namespace Tests\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpServicesAction;
use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpTraceMetricsAction;
use App\Modules\Mcp\Domain\Services\McpTraceIndexExceptionTranslator;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolResult;
use App\Modules\Mcp\Infrastructure\Tools\GetTraceMetricsTool;
use App\Modules\Mcp\Infrastructure\Tools\McpToolFormatter;
use App\Modules\Mcp\Infrastructure\Tools\McpToolServiceFinder;
use App\Modules\Mcp\Infrastructure\Tools\McpToolTimeParser;
use App\Modules\Service\Domain\Actions\FindServicesAction;
use App\Modules\Service\Entities\ServiceObject;
use App\Modules\Trace\Domain\Actions\MakeTraceTimestampPeriodsAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTimestampsAction;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexErrorException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampFieldIndicatorObject;
use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampFieldObject;
use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampsObject;
use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampsObjects;
use App\Modules\Trace\Enums\TraceMetricFieldEnum;
use App\Modules\Trace\Parameters\FindTraceTimestampsParameters;
use App\Modules\Trace\Repositories\Services\TraceTimestampMetricsFactory;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Throwable;

class TraceMetricsToolTest extends TestCase
{
    private ?FindTraceTimestampsParameters $captured = null;

    public function testReadyAnswer(): void
    {
        $result = $this->call(['period' => '1 hour', 'service_ids' => [2], 'statuses' => ['failed']]);

        $this->assertFalse($result->isError);
        $this->assertSame(
            [
                'status'  => 'ready',
                'period'  => '1 hour',
                'step'    => 'min',
                'from'    => '2026-09-28T19:07:00Z',
                'to'      => '2026-09-28T19:08:59Z',
                'metrics' => ['count', 'duration'],
                'points'  => [
                    [
                        'from'     => '2026-09-28T19:07:00Z',
                        'to'       => '2026-09-28T19:07:59Z',
                        'count'    => 12,
                        'duration' => ['avg' => 0.5, 'min' => 0.1, 'max' => 2.0, 'p50' => 0.4, 'p95' => 1.5, 'p99' => 1.9],
                    ],
                    [
                        'from'     => '2026-09-28T19:08:00Z',
                        'to'       => '2026-09-28T19:08:59Z',
                        'count'    => 0,
                        'duration' => ['avg' => null, 'min' => null, 'max' => null, 'p50' => null, 'p95' => null, 'p99' => null],
                    ],
                ],
            ],
            $result->data
        );
        $this->assertSame([2], $this->captured?->serviceIds);
        $this->assertSame(['failed'], $this->captured?->statuses);
    }

    public function testOnlyRequestedMetrics(): void
    {
        $result = $this->call(['period' => '1 hour', 'metrics' => ['cpu']]);

        $point = $result->data['points'][0];

        $this->assertSame(['from', 'to', 'cpu'], array_keys($point));
        $this->assertSame(['avg' => 0.3, 'min' => 0.0, 'max' => 0.9, 'p50' => 0.2, 'p95' => 0.8, 'p99' => 0.9], $point['cpu']);
        $this->assertSame([TraceMetricFieldEnum::Cpu], $this->captured?->fields);
    }

    public function testRangesReachTheGraph(): void
    {
        $this->call(['period' => '1 hour', 'duration_from' => 0.5, 'cpu_to' => 3]);

        $this->assertSame(0.5, $this->captured?->durationFrom);
        $this->assertSame(3.0, $this->captured?->cpuTo);
        $this->assertNull($this->captured?->memoryFrom);
    }

    public function testToIsParsed(): void
    {
        $this->call(['period' => '1 hour', 'to' => '2026-09-28T23:00:00+03:00']);

        $this->assertSame('2026-09-28 20:00:00', $this->captured?->loggedAtTo?->toDateTimeString());
    }

    public function testIndexBuilding(): void
    {
        $result = $this->call(['period' => '1 hour'], new TraceDynamicIndexInProcessException('idx-1'));

        $this->assertFalse($result->isError);
        $this->assertSame('index_building', $result->data['status']);
        $this->assertSame('idx-1', $result->data['index_id']);
        $this->assertSame(10, $result->data['retry_after_seconds']);
        $this->assertStringContainsString('SAME call', $result->data['hint']);
        $this->assertStringContainsString('get_index_status', $result->data['hint']);
    }

    public function testIndexError(): void
    {
        $result = $this->call(['period' => '1 hour'], new TraceDynamicIndexErrorException('disk is full'));

        $this->assertTrue($result->isError);
        $this->assertSame('index_error', $result->data['error']);
        $this->assertStringContainsString('disk is full', $result->data['hint']);
    }

    public function testStepNotAllowed(): void
    {
        $result = $this->call(['period' => '1 day', 'step' => 's5']);

        $this->assertTrue($result->isError);
        $this->assertSame('step_not_allowed', $result->data['error']);
        $this->assertStringContainsString('min30, h, h4, h12', $result->data['hint']);
        $this->assertNull($this->captured);
    }

    public function testUnknownService(): void
    {
        $result = $this->call(['period' => '1 hour', 'service_ids' => [2, 999]]);

        $this->assertTrue($result->isError);
        $this->assertSame('service_not_found', $result->data['error']);
        $this->assertStringContainsString('999', $result->data['hint']);
        $this->assertNull($this->captured);
    }

    public function testInvalidTime(): void
    {
        $result = $this->call(['period' => '1 hour', 'to' => 'yesterday']);

        $this->assertTrue($result->isError);
        $this->assertSame('invalid_time', $result->data['error']);
        $this->assertStringContainsString('"to"', $result->data['hint']);
        $this->assertNull($this->captured);
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function call(array $arguments, ?Throwable $exception = null): McpToolResult
    {
        $graph = $this->createMock(FindTraceTimestampsAction::class);

        if ($exception) {
            $graph->method('handle')->willThrowException($exception);
        } else {
            $graph->method('handle')
                ->willReturnCallback(function (FindTraceTimestampsParameters $parameters): TraceTimestampsObjects {
                    $this->captured = $parameters;

                    return $this->graphResult();
                });
        }

        $services = $this->createMock(FindServicesAction::class);
        $services->method('handle')->willReturn([
            new ServiceObject(id: 2, name: 'pms', apiToken: 'a'),
        ]);

        return new GetTraceMetricsTool(
            new FindMcpTraceMetricsAction(
                new MakeTraceTimestampPeriodsAction(new TraceTimestampMetricsFactory()),
                $graph,
                new McpTraceIndexExceptionTranslator()
            ),
            new McpToolServiceFinder(new FindMcpServicesAction($services)),
            new McpToolTimeParser(),
            new McpToolFormatter()
        )->call(new McpToolArguments($arguments));
    }

    private function graphResult(): TraceTimestampsObjects
    {
        $from = Carbon::parse('2026-09-28 19:07:00', 'UTC');

        return new TraceTimestampsObjects(
            loggedAtFrom: $from,
            items: [
                new TraceTimestampsObject(
                    timestamp: $from->clone(),
                    timestampTo: Carbon::parse('2026-09-28 19:07:59', 'UTC'),
                    fields: [
                        $this->field('count', ['sum' => 12]),
                        $this->field(
                            'dur',
                            ['avg' => 0.5, 'min' => 0.1, 'max' => 2.0, 'p50' => 0.4, 'p95' => 1.5, 'p99' => 1.9]
                        ),
                        $this->field(
                            'cpu',
                            ['avg' => 0.3, 'min' => 0.0, 'max' => 0.9, 'p50' => 0.2, 'p95' => 0.8, 'p99' => 0.9]
                        ),
                    ]
                ),
                new TraceTimestampsObject(
                    timestamp: Carbon::parse('2026-09-28 19:08:00', 'UTC'),
                    timestampTo: Carbon::parse('2026-09-28 19:08:59', 'UTC'),
                    fields: []
                ),
            ]
        );
    }

    /**
     * @param array<string, int|float> $indicators
     */
    private function field(string $field, array $indicators): TraceTimestampFieldObject
    {
        return new TraceTimestampFieldObject(
            field: $field,
            indicators: array_map(
                static fn(string $name, int|float $value) => new TraceTimestampFieldIndicatorObject(name: $name, value: $value),
                array_keys($indicators),
                $indicators
            )
        );
    }
}
