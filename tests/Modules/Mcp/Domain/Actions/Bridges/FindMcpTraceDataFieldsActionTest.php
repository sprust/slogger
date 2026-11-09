<?php

namespace Tests\Modules\Mcp\Domain\Actions\Bridges;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpTraceDataFieldsAction;
use App\Modules\Mcp\Transports\McpTracePeriodTransport;
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
        $result = $this
            ->action(
                traceIds: [
                    't1',
                    't2',
                ],
                details: [
                    't1' => $this->detailWithData(
                        traceId: 't1',
                        children: [
                            $this->dataNode(
                                key: 'request',
                                value: null,
                                children: [
                                    $this->dataNode(
                                        key: 'request.uri',
                                        value: '/api/pay',
                                    ),
                                    $this->dataNode(
                                        key: 'request.method',
                                        value: 'POST',
                                    ),
                                ],
                            ),
                        ],
                    ),
                    't2' => $this->detailWithData(
                        traceId: 't2',
                        children: [
                            $this->dataNode(
                                key: 'request',
                                value: null,
                                children: [
                                    $this->dataNode(
                                        key: 'request.uri',
                                        value: '/api/refund',
                                    ),
                                ],
                            ),
                            $this->dataNode(
                                key: 'response',
                                value: null,
                                children: [
                                    $this->dataNode(
                                        key: 'response.status',
                                        value: 500,
                                    ),
                                ],
                            ),
                        ],
                    ),
                ],
            )
            ->handle($this->parameters());

        $this->assertSame([
            [
                'request.uri',
                '/api/pay',
            ],
            [
                'request.method',
                'POST',
            ],
            [
                'response.status',
                500,
            ],
        ], array_map(static fn(McpTraceDataFieldObject $field) => [
            $field->key,
            $field->example,
        ], $result->fields));

        $this->assertSame(2, $result->tracesCount);

        $this->assertSame([
            'request',
        ], $this->captured?->types);

        $this->assertSame(5, $this->captured->perPage);

        $this->assertSame([
            2,
        ], $this->captured->serviceIds);
    }

    public function testFieldsAreLimited(): void
    {
        $children = array_map(
            fn(int $i) => $this->dataNode(
                key: "k{$i}",
                value: $i,
            ),
            range(
                1,
                250,
            )
        );
        $result = $this
            ->action(
                traceIds: [
                    't1',
                ],
                details: [
                    't1' => $this->detailWithData(
                        traceId: 't1',
                        children: $children,
                    ),
                ],
            )
            ->handle($this->parameters());

        $this->assertCount(200, $result->fields);
    }

    public function testNoTraces(): void
    {
        $result = $this
            ->action(
                traceIds: [],
                details: [],
            )
            ->handle($this->parameters());

        $this->assertSame([], $result->fields);

        $this->assertSame(0, $result->tracesCount);
    }

    private function parameters(): FindMcpTraceDataFieldsParameters
    {
        return new FindMcpTraceDataFieldsParameters(
            serviceIds: [
                2,
            ],
            period: $this->period(),
            type: 'request',
        );
    }

    /**
     * @param string[]                         $traceIds
     * @param array<string, TraceDetailObject> $details
     */
    private function action(array $traceIds, array $details): FindMcpTraceDataFieldsAction
    {
        $search = $this->createMock(FindTracesAction::class);

        $search
            ->method('handle')
            ->willReturnCallback(function (TraceFindParameters $parameters) use ($traceIds) {
                $this->captured = $parameters;

                return $this->traceItems(
                    traceIds: $traceIds,
                );
            });

        $detail = $this->createMock(FindTraceDetailAction::class);

        $detail->method('handle')->willReturnCallback(static fn(string $traceId) => $details[$traceId] ?? null);

        return new FindMcpTraceDataFieldsAction(
            findTracesAction: $search,
            findTraceDetailAction: $detail,
            mcpTracePeriodTransport: new McpTracePeriodTransport(),
        );
    }
}
