<?php

namespace Tests\Modules\Mcp\Domain\Actions\Bridges;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpTracesAction;
use App\Modules\Mcp\Domain\Exceptions\McpTraceDataFilterInvalidException;
use App\Modules\Mcp\Domain\Services\McpTraceDataFilterParser;
use App\Modules\Mcp\Domain\Services\McpTracePeriodMapper;
use App\Modules\Mcp\Parameters\FindMcpTracesParameters;
use App\Modules\Trace\Domain\Actions\Queries\FindTracesAction;
use App\Modules\Trace\Parameters\TraceFindParameters;
use PHPUnit\Framework\TestCase;
use Tests\Modules\Mcp\McpTraceQueryTestTrait;

class FindMcpTracesActionTest extends TestCase
{
    use McpTraceQueryTestTrait;

    private ?TraceFindParameters $captured = null;

    public function testBuildsTheSearch(): void
    {
        $this->action()->handle(
            new FindMcpTracesParameters(
                serviceIds: [2],
                period: $this->period(),
                types: ['request'],
                statuses: ['failed'],
                durationFrom: 0.5,
                durationTo: 3.0,
                dataFilter: ['response.status >= 500'],
                dataFields: ['request.uri'],
                page: 3
            )
        );

        $this->assertSame(3, $this->captured?->page);
        $this->assertSame(50, $this->captured->perPage);
        $this->assertSame([2], $this->captured->serviceIds);
        $this->assertSame(['request'], $this->captured->types);
        $this->assertSame(['failed'], $this->captured->statuses);
        $this->assertSame(0.5, $this->captured->durationFrom);
        $this->assertSame(3.0, $this->captured->durationTo);
        $this->assertSame('dt.response.status', $this->captured->data?->filter[0]->field);
        $this->assertSame(['request.uri'], $this->captured->data->fields);
        $this->assertNull($this->captured->traceId);
        $this->assertSame('2026-09-28 10:00:00', $this->captured->loggingPeriod?->from?->toDateTimeString());
    }

    public function testWithoutDataNothingIsFilteredByData(): void
    {
        $this->action()->handle(new FindMcpTracesParameters(serviceIds: [2], period: $this->period()));

        $this->assertNull($this->captured?->data);
    }

    public function testTagsAndDataFilterGoTogether(): void
    {
        $this->action()->handle(
            new FindMcpTracesParameters(
                serviceIds: [2],
                period: $this->period(),
                tags: ['api'],
                dataFilter: ['response.status >= 500']
            )
        );

        $this->assertSame(['api'], $this->captured?->tags);
        $this->assertSame('dt.response.status', $this->captured->data?->filter[0]->field);
    }

    public function testInvalidDataFilter(): void
    {
        $this->expectException(McpTraceDataFilterInvalidException::class);

        $this->action()->handle(
            new FindMcpTracesParameters(serviceIds: [2], period: $this->period(), dataFilter: ['status ~ 5'])
        );
    }

    private function action(): FindMcpTracesAction
    {
        $search = $this->createMock(FindTracesAction::class);
        $search->method('handle')->willReturnCallback(function (TraceFindParameters $parameters) {
            $this->captured = $parameters;

            return $this->traceItems(['t1']);
        });

        return new FindMcpTracesAction(
            findTracesAction: $search,
            dataFilterParser: new McpTraceDataFilterParser(),
            periodMapper: new McpTracePeriodMapper()
        );
    }
}
