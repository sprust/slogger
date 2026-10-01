<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpTraceTreeAction;
use App\Modules\Mcp\Domain\Actions\Queries\FindMcpSettingsAction;
use App\Modules\Mcp\Entities\Bridges\McpTraceTreeNodeObject;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolInterface;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolProperty;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolPropertyTypeEnum;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolResult;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolSchema;

readonly class GetTraceTreeTool implements McpToolInterface
{
    public function __construct(
        private FindMcpTraceTreeAction $findMcpTraceTreeAction,
        private FindMcpSettingsAction $findMcpSettingsAction,
        private McpToolFormatter $formatter
    ) {
    }

    public function name(): string
    {
        return 'get_trace_tree';
    }

    public function title(): string
    {
        return 'Get trace tree';
    }

    public function description(): string
    {
        return 'The tree of calls a trace belongs to, branch by branch: without parent_trace_id the top '
            . 'of the tree, with it the children of that node, paged by cursor. The first call for a tree '
            . 'starts building it in the background and answers "tree_building": repeat the same call '
            . 'later.';
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
                name: 'parent_trace_id',
                type: McpToolPropertyTypeEnum::String,
                description: 'Return the children of this node.',
                max: 255
            ),
            new McpToolProperty(
                name: 'cursor',
                type: McpToolPropertyTypeEnum::String,
                description: 'next_cursor of the previous call for the same parent_trace_id.',
                max: 1024
            ),
        ]);
    }

    public function call(McpToolArguments $arguments): McpToolResult
    {
        $traceId       = $arguments->string('trace_id');
        $parentTraceId = $arguments->stringNull('parent_trace_id');

        $tree = $this->findMcpTraceTreeAction->handle(
            traceId: $traceId,
            parentTraceId: $parentTraceId,
            cursor: $arguments->stringNull('cursor'),
            limit: $this->findMcpSettingsAction->handle()->treeNodesLimit
        );

        if (is_null($tree)) {
            return $this->formatter->traceNotFound($traceId);
        }

        $notReady = $this->formatter->treeNotReady(
            tree: $tree,
            buildingHint: 'The tree is being built in the background. Repeat the SAME get_trace_tree call later.'
        );

        if (!is_null($notReady)) {
            return $notReady;
        }

        return new McpToolResult(
            data: [
                'status'          => 'ready',
                'root_trace_id'   => $tree->rootTraceId,
                'parent_trace_id' => $parentTraceId,
                'nodes'           => array_map(
                    fn(McpTraceTreeNodeObject $node) => $this->formatter->treeNode($node),
                    $tree->nodes
                ),
                'next_cursor'     => $tree->nextCursor,
            ]
        );
    }
}
