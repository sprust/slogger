<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpTraceTreeFilteredAction;
use App\Modules\Mcp\Domain\Actions\Queries\FindMcpSettingsAction;
use App\Modules\Mcp\Entities\Bridges\McpTraceTreeNodeObject;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArgumentsException;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolInterface;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolProperty;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolPropertyTypeEnum;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolResult;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolSchema;
use App\Modules\Trace\Parameters\TraceTreeFilterParameters;

readonly class SearchTraceTreeTool implements McpToolInterface
{
    private const int MAX_FILTER_VALUES = 20;

    public function __construct(
        private FindMcpTraceTreeFilteredAction $findMcpTraceTreeFilteredAction,
        private FindMcpSettingsAction $findMcpSettingsAction,
        private McpToolFormatter $formatter
    ) {
    }

    public function name(): string
    {
        return 'search_trace_tree';
    }

    public function title(): string
    {
        return 'Find in trace tree';
    }

    public function description(): string
    {
        return 'Nodes of an already built trace tree that match the filters: any of the given values per '
            . 'filter, all given filters at once. Pass at least one filter. It does not build the tree: '
            . 'call get_trace_tree first.';
    }

    public function schema(): McpToolSchema
    {
        return new McpToolSchema([
            new McpToolProperty(
                name: 'trace_id',
                type: McpToolPropertyTypeEnum::String,
                description: 'Any trace id of the tree.',
                required: true,
                max: 255
            ),
            new McpToolProperty(
                name: 'service_ids',
                type: McpToolPropertyTypeEnum::IntegerList,
                description: 'Service ids from get_services.',
                max: self::MAX_FILTER_VALUES
            ),
            new McpToolProperty(
                name: 'types',
                type: McpToolPropertyTypeEnum::StringList,
                description: 'Trace types.',
                max: self::MAX_FILTER_VALUES
            ),
            new McpToolProperty(
                name: 'tags',
                type: McpToolPropertyTypeEnum::StringList,
                description: 'Trace tags.',
                max: self::MAX_FILTER_VALUES
            ),
            new McpToolProperty(
                name: 'statuses',
                type: McpToolPropertyTypeEnum::StringList,
                description: 'Trace statuses, for example failed.',
                max: self::MAX_FILTER_VALUES
            ),
        ]);
    }

    public function call(McpToolArguments $arguments): McpToolResult
    {
        $traceId    = $arguments->string('trace_id');
        $parameters = new TraceTreeFilterParameters(
            serviceIds: $arguments->intList('service_ids'),
            types: $arguments->stringList('types'),
            tags: $arguments->stringList('tags'),
            statuses: $arguments->stringList('statuses'),
        );

        $filtersCount = count($parameters->serviceIds) + count($parameters->types)
            + count($parameters->tags) + count($parameters->statuses);

        if ($filtersCount === 0) {
            throw new McpToolArgumentsException(
                'pass at least one of service_ids, types, tags, statuses'
            );
        }

        $tree = $this->findMcpTraceTreeFilteredAction->handle(
            traceId: $traceId,
            parameters: $parameters,
            limit: $this->findMcpSettingsAction->handle()->treeNodesLimit
        );

        if (is_null($tree)) {
            return $this->formatter->traceNotFound($traceId);
        }

        $notReady = $this->formatter->treeNotReady(
            tree: $tree,
            buildingHint: 'The tree is not built yet. Call get_trace_tree with this trace_id until it is '
            . 'ready, then repeat this call.'
        );

        if (!is_null($notReady)) {
            return $notReady;
        }

        return new McpToolResult(
            data: [
                'root_trace_id' => $tree->rootTraceId,
                'nodes'         => array_map(
                    fn(McpTraceTreeNodeObject $node) => $this->formatter->treeNode($node),
                    $tree->nodes
                ),
                'matched_count' => $tree->matchedCount,
                'truncated'     => $tree->truncated,
            ]
        );
    }
}
