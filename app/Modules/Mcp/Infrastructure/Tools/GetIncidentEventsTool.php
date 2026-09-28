<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpIncidentEventsAction;
use App\Modules\Mcp\Entities\Bridges\McpIncidentEventObject;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolInterface;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolProperty;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolPropertyTypeEnum;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolResult;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolSchema;

readonly class GetIncidentEventsTool implements McpToolInterface
{
    public function __construct(
        private FindMcpIncidentEventsAction $findMcpIncidentEventsAction,
        private McpToolFormatter $formatter
    ) {
    }

    public function name(): string
    {
        return 'get_incident_events';
    }

    public function title(): string
    {
        return 'Get incident events';
    }

    public function description(): string
    {
        return sprintf(
            'One incident and its events, newest first, %d per page: when each event occurred and the '
            . 'numbers the watcher recorded for it. No index needed.',
            FindMcpIncidentEventsAction::PER_PAGE
        );
    }

    public function schema(): McpToolSchema
    {
        return new McpToolSchema([
            new McpToolProperty(
                name: 'incident_id',
                type: McpToolPropertyTypeEnum::String,
                description: 'Incident id from list_incidents.',
                required: true,
                max: 64
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
        $incidentId = $arguments->string('incident_id');
        $page       = $arguments->intNull('page') ?? 1;

        $result = $this->findMcpIncidentEventsAction->handle($incidentId, $page);

        if (is_null($result)) {
            return $this->formatter->error(
                error: 'incident_not_found',
                hint: "Incident [$incidentId] not found. Take ids from list_incidents of the same installation."
            );
        }

        return new McpToolResult(
            data: [
                'incident' => $this->formatter->incident($result->incident),
                'events'   => array_map(
                    fn(McpIncidentEventObject $event) => [
                        'occurred_at' => $this->formatter->time($event->occurredAt),
                        'numbers'     => $event->numbers,
                    ],
                    $result->items
                ),
                'page'     => $page,
                'has_more' => $result->hasMore,
            ]
        );
    }
}
