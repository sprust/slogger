<?php

namespace Tests\Modules\Mcp\Domain\Actions\Bridges;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpTraceFacetsAction;
use App\Modules\Mcp\Domain\Services\McpTracePeriodMapper;
use App\Modules\Mcp\Parameters\FindMcpTraceFacetsParameters;
use App\Modules\Trace\Domain\Actions\Queries\FindStatusesAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTagsAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTypesAction;
use App\Modules\Trace\Entities\Trace\TraceStringFieldObject;
use App\Modules\Trace\Parameters\TraceFindStatusesParameters;
use App\Modules\Trace\Parameters\TraceFindTagsParameters;
use App\Modules\Trace\Parameters\TraceFindTypesParameters;
use PHPUnit\Framework\TestCase;
use Tests\Modules\Mcp\McpTraceQueryTestTrait;

class FindMcpTraceFacetsActionTest extends TestCase
{
    use McpTraceQueryTestTrait;

    private ?TraceFindTypesParameters $typesParameters       = null;
    private ?TraceFindStatusesParameters $statusesParameters = null;
    private ?TraceFindTagsParameters $tagsParameters         = null;

    public function testEachFacetGetsTheOtherFilters(): void
    {
        $this->action()->handle($this->parameters(types: ['request'], statuses: ['failed']));

        $this->assertSame([2], $this->typesParameters?->serviceIds);
        $this->assertSame(['failed'], $this->typesParameters->statuses);
        $this->assertSame(['request'], $this->statusesParameters?->types);
        $this->assertSame(['request'], $this->tagsParameters?->types);
        $this->assertSame(['failed'], $this->tagsParameters->statuses);
        $this->assertSame('2026-09-28 10:00:00', $this->typesParameters->loggingPeriod?->from?->toDateTimeString());
        $this->assertSame('2026-09-28 12:59:59.999999', $this->typesParameters->loggingPeriod->to?->format('Y-m-d H:i:s.u'));
    }

    public function testWithoutServicesNoFacetIsFilteredByService(): void
    {
        $this->action()->handle(
            new FindMcpTraceFacetsParameters(serviceIds: [], period: $this->period(), types: [], statuses: [], limit: 50)
        );

        $this->assertSame([], $this->typesParameters?->serviceIds);
        $this->assertSame([], $this->statusesParameters?->serviceIds);
        $this->assertSame([], $this->tagsParameters?->serviceIds);
    }

    public function testListsAreSortedByCountAndCut(): void
    {
        $facets = $this->action()->handle($this->parameters(limit: 2));

        $this->assertSame(
            [['job', 30], ['request', 30]],
            array_map(static fn(TraceStringFieldObject $item) => [$item->name, $item->count], $facets->types->items)
        );
        $this->assertTrue($facets->types->truncated);
        $this->assertFalse($facets->statuses->truncated);
    }

    /**
     * @param string[] $types
     * @param string[] $statuses
     */
    private function parameters(array $types = [], array $statuses = [], int $limit = 50): FindMcpTraceFacetsParameters
    {
        return new FindMcpTraceFacetsParameters(
            serviceIds: [2],
            period: $this->period(),
            types: $types,
            statuses: $statuses,
            limit: $limit
        );
    }

    private function action(): FindMcpTraceFacetsAction
    {
        $types = $this->createMock(FindTypesAction::class);
        $types->method('handle')->willReturnCallback(function (TraceFindTypesParameters $parameters): array {
            $this->typesParameters = $parameters;

            return [
                new TraceStringFieldObject(name: 'command', count: 5),
                new TraceStringFieldObject(name: 'request', count: 30),
                new TraceStringFieldObject(name: 'job', count: 30),
            ];
        });

        $statuses = $this->createMock(FindStatusesAction::class);
        $statuses->method('handle')->willReturnCallback(function (TraceFindStatusesParameters $parameters): array {
            $this->statusesParameters = $parameters;

            return [new TraceStringFieldObject(name: 'failed', count: 3)];
        });

        $tags = $this->createMock(FindTagsAction::class);
        $tags->method('handle')->willReturnCallback(function (TraceFindTagsParameters $parameters): array {
            $this->tagsParameters = $parameters;

            return [];
        });

        return new FindMcpTraceFacetsAction(
            findTypesAction: $types,
            findStatusesAction: $statuses,
            findTagsAction: $tags,
            periodMapper: new McpTracePeriodMapper()
        );
    }
}
