<?php

namespace Tests\Modules\Mcp\Domain\Actions;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpTraceMetricsAction;
use App\Modules\Mcp\Domain\Services\McpTraceIndexExceptionTranslator;
use App\Modules\Mcp\Domain\Exceptions\McpTraceIndexBuildingException;
use App\Modules\Mcp\Domain\Exceptions\McpTraceIndexFailedException;
use App\Modules\Mcp\Domain\Exceptions\McpTraceMetricsStepNotAllowedException;
use App\Modules\Mcp\Parameters\FindMcpTraceMetricsParameters;
use App\Modules\Trace\Domain\Actions\MakeTraceTimestampPeriodsAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTimestampsAction;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexErrorException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;
use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampsObjects;
use App\Modules\Trace\Enums\TraceMetricFieldEnum;
use App\Modules\Trace\Enums\TraceTimestampEnum;
use App\Modules\Trace\Enums\TraceTimestampPeriodEnum;
use App\Modules\Trace\Parameters\FindTraceTimestampsParameters;
use App\Modules\Trace\Repositories\Services\TraceTimestampMetricsFactory;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Throwable;

class FindMcpTraceMetricsActionTest extends TestCase
{
    /**
     * @return array<string, array{TraceTimestampPeriodEnum, TraceTimestampEnum}>
     */
    public static function defaultSteps(): array
    {
        return [
            '5 minutes'  => [TraceTimestampPeriodEnum::Minute5, TraceTimestampEnum::S5],
            '30 minutes' => [TraceTimestampPeriodEnum::Minute30, TraceTimestampEnum::S30],
            '1 hour'     => [TraceTimestampPeriodEnum::Hour, TraceTimestampEnum::Min],
            '4 hours'    => [TraceTimestampPeriodEnum::Hour4, TraceTimestampEnum::Min5],
            '12 hours'   => [TraceTimestampPeriodEnum::Hour12, TraceTimestampEnum::Min30],
            '1 day'      => [TraceTimestampPeriodEnum::Day, TraceTimestampEnum::Min30],
        ];
    }

    #[DataProvider('defaultSteps')]
    public function testDefaultStepOfPeriod(TraceTimestampPeriodEnum $period, TraceTimestampEnum $expected): void
    {
        $captured = null;

        $result = $this->action($captured)->handle($this->parameters(period: $period));

        $this->assertSame($expected, $captured?->timestampStep);
        $this->assertSame($expected, $result->step);
    }

    public function testStepNotAllowedForPeriod(): void
    {
        $captured = null;

        try {
            $this->action($captured)->handle(
                $this->parameters(period: TraceTimestampPeriodEnum::Day, step: TraceTimestampEnum::S5)
            );

            $this->fail('No exception thrown');
        } catch (McpTraceMetricsStepNotAllowedException $exception) {
            $this->assertSame(
                [TraceTimestampEnum::Min30, TraceTimestampEnum::H, TraceTimestampEnum::H4, TraceTimestampEnum::H12],
                $exception->allowedSteps
            );
        }

        $this->assertNull($captured);
    }

    public function testArgumentsReachTheGraphUnchanged(): void
    {
        $captured = null;

        $this->action($captured)->handle(
            new FindMcpTraceMetricsParameters(
                period: TraceTimestampPeriodEnum::Hour4,
                step: TraceTimestampEnum::H,
                metrics: [TraceMetricFieldEnum::Count, TraceMetricFieldEnum::Cpu],
                to: Carbon::parse('2026-09-28 23:07:00', 'Europe/Moscow'),
                serviceIds: [2, 3],
                types: ['request'],
                tags: ['api'],
                statuses: ['failed'],
                durationFrom: 0.5,
                durationTo: 2.0,
                memoryFrom: 1.0,
                memoryTo: 5.0,
                cpuFrom: 0.1,
                cpuTo: 0.9
            )
        );

        $this->assertNotNull($captured);
        $this->assertSame(TraceTimestampPeriodEnum::Hour4, $captured->timestampPeriod);
        $this->assertSame(TraceTimestampEnum::H, $captured->timestampStep);
        $this->assertSame([TraceMetricFieldEnum::Count, TraceMetricFieldEnum::Cpu], $captured->fields);
        $this->assertSame('2026-09-28 20:07:00', $captured->loggedAtTo?->toDateTimeString());
        $this->assertSame('UTC', $captured->loggedAtTo?->getTimezone()->getName());
        $this->assertSame([2, 3], $captured->serviceIds);
        $this->assertSame(['request'], $captured->types);
        $this->assertSame(['api'], $captured->tags);
        $this->assertSame(['failed'], $captured->statuses);
        $this->assertSame([0.5, 2.0, 1.0, 5.0, 0.1, 0.9], [
            $captured->durationFrom,
            $captured->durationTo,
            $captured->memoryFrom,
            $captured->memoryTo,
            $captured->cpuFrom,
            $captured->cpuTo,
        ]);
        $this->assertNull($captured->dataFields);
        $this->assertNull($captured->traceIds);
        $this->assertNull($captured->data);
        $this->assertNull($captured->hasProfiling);
    }

    public function testWithoutServicesTheGraphGetsNoServiceFilter(): void
    {
        $captured = null;

        $this->action($captured)->handle($this->parameters());

        $this->assertNull($captured?->serviceIds);
        $this->assertNull($captured?->loggedAtTo);
    }

    public function testIndexInProcessBecomesBuilding(): void
    {
        try {
            $this->failingAction(new TraceDynamicIndexInProcessException('idx-1'))->handle($this->parameters());

            $this->fail('No exception thrown');
        } catch (McpTraceIndexBuildingException $exception) {
            $this->assertSame('idx-1', $exception->indexId);
        }
    }

    public function testIndexErrorBecomesFailed(): void
    {
        $this->expectException(McpTraceIndexFailedException::class);
        $this->expectExceptionMessage('disk is full');

        $this->failingAction(new TraceDynamicIndexErrorException('disk is full'))->handle($this->parameters());
    }

    public function testIndexNotInitBecomesFailed(): void
    {
        $this->expectException(McpTraceIndexFailedException::class);

        $this->failingAction(new TraceDynamicIndexNotInitException())->handle($this->parameters());
    }

    private function parameters(
        TraceTimestampPeriodEnum $period = TraceTimestampPeriodEnum::Hour,
        ?TraceTimestampEnum $step = null
    ): FindMcpTraceMetricsParameters {
        return new FindMcpTraceMetricsParameters(
            period: $period,
            step: $step,
            metrics: [TraceMetricFieldEnum::Count],
            to: null
        );
    }

    private function action(?FindTraceTimestampsParameters &$captured): FindMcpTraceMetricsAction
    {
        $graph = $this->createMock(FindTraceTimestampsAction::class);
        $graph->method('handle')
            ->willReturnCallback(
                static function (FindTraceTimestampsParameters $parameters) use (&$captured): TraceTimestampsObjects {
                    $captured = $parameters;

                    return new TraceTimestampsObjects(loggedAtFrom: Carbon::now(), items: []);
                }
            );

        return new FindMcpTraceMetricsAction(
            new MakeTraceTimestampPeriodsAction(new TraceTimestampMetricsFactory()),
            $graph,
            new McpTraceIndexExceptionTranslator()
        );
    }

    private function failingAction(Throwable $exception): FindMcpTraceMetricsAction
    {
        $graph = $this->createMock(FindTraceTimestampsAction::class);
        $graph->method('handle')->willThrowException($exception);

        return new FindMcpTraceMetricsAction(
            new MakeTraceTimestampPeriodsAction(new TraceTimestampMetricsFactory()),
            $graph,
            new McpTraceIndexExceptionTranslator()
        );
    }
}
