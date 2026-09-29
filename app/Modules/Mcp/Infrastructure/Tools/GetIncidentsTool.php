<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpIncidentsAction;
use App\Modules\Mcp\Entities\Bridges\McpIncidentObject;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolInterface;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolProperty;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolPropertyTypeEnum;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolResult;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolSchema;
use App\Modules\Mcp\Parameters\FindMcpIncidentsParameters;
use App\Modules\Watcher\Enums\WatcherIncidentStatusEnum;

readonly class GetIncidentsTool implements McpToolInterface
{
    public function __construct(
        private FindMcpIncidentsAction $findMcpIncidentsAction,
        private McpToolFormatter $formatter
    ) {
    }

    public function name(): string
    {
        return 'get_incidents';
    }

    public function title(): string
    {
        return 'List incidents';
    }

    public function description(): string
    {
        return sprintf(
            'Incidents raised by SLogger watchers, open ones first, then newest first, %d per page. '
            . 'Each incident carries its watcher with the service ids the watcher is limited to '
            . '(empty means all services): filter by service yourself. No index needed.',
            FindMcpIncidentsAction::PER_PAGE
        );
    }

    public function schema(): McpToolSchema
    {
        return new McpToolSchema([
            new McpToolProperty(
                name: 'status',
                type: McpToolPropertyTypeEnum::String,
                description: 'Only incidents in this status.',
                enum: array_map(
                    static fn(WatcherIncidentStatusEnum $status) => $status->value,
                    WatcherIncidentStatusEnum::cases()
                )
            ),
            new McpToolProperty(
                name: 'watcher_id',
                type: McpToolPropertyTypeEnum::Integer,
                description: 'Only incidents of this watcher.',
                min: 1
            ),
            new McpToolProperty(
                name: 'page',
                type: McpToolPropertyTypeEnum::Integer,
                description: 'Page number, starting at 1.',
                min: 1
            ),
        ]);
    }

    public function call(McpToolArguments $arguments): McpToolResult
    {
        $status = $arguments->stringNull('status');
        $page   = $arguments->intNull('page') ?? 1;

        $result = $this->findMcpIncidentsAction->handle(
            new FindMcpIncidentsParameters(
                status: is_null($status) ? null : WatcherIncidentStatusEnum::from($status),
                watcherId: $arguments->intNull('watcher_id'),
                page: $page
            )
        );

        return new McpToolResult(
            data: [
                'incidents' => array_map(
                    fn(McpIncidentObject $incident) => $this->formatter->incident($incident),
                    $result->items
                ),
                'page'      => $page,
                'has_more'  => $result->hasMore,
            ]
        );
    }
}
