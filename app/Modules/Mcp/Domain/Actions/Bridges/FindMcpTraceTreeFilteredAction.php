<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Bridges;

use App\Modules\Mcp\Domain\Services\McpTraceTreeNodeFactory;
use App\Modules\Mcp\Entities\Bridges\McpTraceTreeObject;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTreeFilteredAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTreeStateAction;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeRawObject;
use App\Modules\Trace\Enums\TraceTreeCacheStateStatusEnum;
use App\Modules\Mcp\Parameters\TraceTreeFilterParameters;
use App\Modules\Mcp\Transports\TraceParametersTransport;
use App\Services\Clickhouse\ClickhouseQueryException;
use App\Modules\Mcp\Transports\TraceObjectTransport;
use App\Modules\Mcp\Enums\TraceTreeCacheStateStatusEnum as McpTraceTreeCacheStateStatusEnum;

readonly class FindMcpTraceTreeFilteredAction
{
    public function __construct(private FindTraceTreeStateAction $findTraceTreeStateAction, private FindTraceTreeFilteredAction $findTraceTreeFilteredAction, private McpTraceTreeNodeFactory $nodeFactory, private TraceObjectTransport $traceObjectTransport, private TraceParametersTransport $traceParametersTransport)
    {
    }

    /**
     * @throws ClickhouseQueryException
     */
    public function handle(string $traceId, TraceTreeFilterParameters $parameters, int $limit): ?McpTraceTreeObject
    {
        $tree = $this->findTraceTreeStateAction->handle($traceId);

        if (is_null($tree)) {
            return null;
        }

        $state = $tree->state;

        if (is_null($state) || $state->status !== TraceTreeCacheStateStatusEnum::Finished) {
            return new McpTraceTreeObject(
                rootTraceId: $tree->rootTraceId,
                status: $state === null ? null : McpTraceTreeCacheStateStatusEnum::from($state->status->value),
                error: $state?->error,
                nodes: [],
                nextCursor: null,
                matchedCount: 0,
                truncated: false,
            );
        }

        $filtered = $this->findTraceTreeFilteredAction->handle(
            rootTraceId: $tree->rootTraceId,
            parameters: $this->traceParametersTransport->toTraceTreeFilterParameters($parameters),
        );
        $matched = array_values(
            array_filter(
                $filtered->items,
                static fn(TraceTreeRawObject $node) => self::matches(
                    node: $node,
                    parameters: $parameters,
                ),
            )
        );

        return new McpTraceTreeObject(
            rootTraceId: $tree->rootTraceId,
            status: McpTraceTreeCacheStateStatusEnum::from($state->status->value),
            error: null,
            nodes: $this->nodeFactory->make(
                nodes: array_map(
                    fn(TraceTreeRawObject $node) => $this->traceObjectTransport->toTraceTreeNodeParameters($node),
                    array_slice(
                        $matched,
                        0,
                        $limit,
                    )
                ),
                childrenCounts: null,
            ),
            nextCursor: null,
            matchedCount: $filtered->matchedCount,
            truncated: $filtered->truncated || count($matched) > $limit,
        );
    }

    private static function matches(TraceTreeRawObject $node, TraceTreeFilterParameters $parameters): bool
    {
        if (
            count($parameters->serviceIds) > 0 && !in_array(
                $node->serviceId,
                $parameters->serviceIds,
                true,
            )
        ) {
            return false;
        }

        if (
            count($parameters->types) > 0 && !in_array(
                $node->type,
                $parameters->types,
                true,
            )
        ) {
            return false;
        }

        if (
            count($parameters->statuses) > 0 && !in_array(
                $node->status,
                $parameters->statuses,
                true,
            )
        ) {
            return false;
        }

        return count($parameters->tags) === 0 || count(array_intersect($node->tags, $parameters->tags)) > 0;
    }
}
