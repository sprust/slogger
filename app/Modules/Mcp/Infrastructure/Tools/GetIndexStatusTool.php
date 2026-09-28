<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpIndexStatusAction;
use App\Modules\Mcp\Enums\McpIndexStatusEnum;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolInterface;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolProperty;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolPropertyTypeEnum;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolResult;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolSchema;

readonly class GetIndexStatusTool implements McpToolInterface
{
    public function __construct(
        private FindMcpIndexStatusAction $findMcpIndexStatusAction
    ) {
    }

    public function name(): string
    {
        return 'get_index_status';
    }

    public function title(): string
    {
        return 'Get index status';
    }

    public function description(): string
    {
        return 'Status of a trace dynamic index by the index_id of an "index_building" answer: building '
            . '(with progress from 0 to 1, or null while it waits in the queue), ready, error or not_found '
            . '(deleted or expired). When it is ready, repeat the call that returned index_building. '
            . 'No index needed.';
    }

    public function schema(): McpToolSchema
    {
        return new McpToolSchema([
            new McpToolProperty(
                name: 'index_id',
                type: McpToolPropertyTypeEnum::String,
                description: 'Index id from an "index_building" answer.',
                required: true,
                max: 64
            ),
        ]);
    }

    public function call(McpToolArguments $arguments): McpToolResult
    {
        $status = $this->findMcpIndexStatusAction->handle($arguments->string('index_id'));

        $data = [
            'index_id' => $status->indexId,
            'status'   => $status->status->value,
        ];

        if ($status->status === McpIndexStatusEnum::Building) {
            $data['progress']            = $status->progress;
            $data['retry_after_seconds'] = 10;
        }

        if ($status->status === McpIndexStatusEnum::Error) {
            $data['error'] = $status->error;
        }

        return new McpToolResult(data: $data);
    }
}
