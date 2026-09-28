<?php

namespace Tests\Modules\Mcp\Domain\Actions\Bridges;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpTraceDataFieldsAction;
use App\Modules\Mcp\Domain\Services\McpTraceIndexExceptionTranslator;
use App\Modules\Mcp\Domain\Services\McpTracePeriodMapper;
use App\Modules\Mcp\Entities\Bridges\McpTraceDataFieldObject;
use App\Modules\Mcp\Parameters\FindMcpTraceDataFieldsParameters;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceDetailAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTracesAction;
use App\Modules\Trace\Entities\Trace\TraceDetailObject;
use App\Modules\Trace\Parameters\TraceFindParameters;
use PHPUnit\Framework\TestCase;
use Tests\Modules\Mcp\McpTraceQueryTestTrait;

class FindMcpTraceDataFieldsActionTest extends TestCase
{
    use McpTraceQueryTestTrait;

    private ?TraceFindParameters $captured = null;

    public function testCollectsLeafKeysInOrderOfFirstAppearance(): void
    {
        $result = $this->action(['t1', 't2'], [
            't1' => $this->detailWithData('t1', [
                $this->dataNode('request', null, [
                    $this->dataNode('request.uri', '/api/pay'),
                    $this->dataNode('request.method', 'POST'),
                ]),
            ]),
            't2' => $this->detailWithData('t2', [
                $this->dataNode('request', null, [$this->dataNode('request.uri', '/api/refund')]),
                $this->dataNode('response', null, [$this->dataNode('response.status', 500)]),
            ]),
        ])->handle($this->parameters());

        $this->assertSame(
            [['request.uri', '/api/pay'], ['request.method', 'POST'], ['response.status', 500]],
            array_map(static fn(McpTraceDataFieldObject $field) => [$field->key, $field->example], $result->fields)
        );
        $this->assertSame(2, $result->tracesCount);
        $this->assertSame(['request'], $this->captured?->types);
        $this->assertSame(5, $this->captured->perPage);
        $this->assertSame([2], $this->captured->serviceIds);
    }

    public function testFieldsAreLimited(): void
    {
        $children = array_map(fn(int $i) => $this->dataNode("k$i", $i), range(1, 250));

        $result = $this->action(['t1'], ['t1' => $this->detailWithData('t1', $children)])->handle($this->parameters());

        $this->assertCount(200, $result->fields);
    }

    public function testNoTraces(): void
    {
        $result = $this->action([], [])->handle($this->parameters());

        $this->assertSame([], $result->fields);
        $this->assertSame(0, $result->tracesCount);
    }

    private function parameters(): FindMcpTraceDataFieldsParameters
    {
        return new FindMcpTraceDataFieldsParameters(serviceIds: [2], period: $this->period(), type: 'request');
    }

    /**
     * @param string[]                         $traceIds
     * @param array<string, TraceDetailObject> $details
     */
    private function action(array $traceIds, array $details): FindMcpTraceDataFieldsAction
    {
        $search = $this->createMock(FindTracesAction::class);
        $search->method('handle')->willReturnCallback(function (TraceFindParameters $parameters) use ($traceIds) {
            $this->captured = $parameters;

            return $this->traceItems($traceIds);
        });

        $detail = $this->createMock(FindTraceDetailAction::class);
        $detail->method('handle')->willReturnCallback(static fn(string $traceId) => $details[$traceId] ?? null);

        return new FindMcpTraceDataFieldsAction(
            $search,
            $detail,
            new McpTraceIndexExceptionTranslator(),
            new McpTracePeriodMapper()
        );
    }
}
