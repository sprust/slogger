<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Entities\Bridges\McpIncidentObject;
use App\Modules\Mcp\Entities\Bridges\McpTraceTreeNodeObject;
use App\Modules\Mcp\Entities\Bridges\McpTraceTreeObject;
use App\Modules\Trace\Entities\Trace\TraceServiceObject;
use App\Modules\Trace\Enums\TraceTreeCacheStateStatusEnum;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolResult;
use Illuminate\Support\Carbon;

readonly class McpToolFormatter
{
    public function time(?Carbon $time): ?string
    {
        return $time?->copy()->utc()->toIso8601ZuluString();
    }

    public function error(string $error, string $hint): McpToolResult
    {
        return new McpToolResult(
            data: [
                'error' => $error,
                'hint'  => $hint,
            ],
            isError: true
        );
    }

    public function invalidTime(string $argument): McpToolResult
    {
        return $this->error(
            error: 'invalid_time',
            hint: sprintf('Argument "%s" is not an ISO 8601 time, for example 2026-09-28T20:00:00Z.', $argument)
        );
    }

    /**
     * The trace storage refused the query or did not answer it in time.
     */
    public function queryFailed(): McpToolResult
    {
        return $this->error(
            error: 'query_failed',
            hint: 'The trace storage could not answer this query. Try a shorter period, fewer services '
            . 'or narrower filters.'
        );
    }

    /**
     * @param int[] $serviceIds
     */
    public function serviceNotFound(array $serviceIds): McpToolResult
    {
        return $this->error(
            error: 'service_not_found',
            hint: sprintf(
                'No services with ids [%s]. Call get_services to get the ids.',
                implode(', ', $serviceIds)
            )
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function incident(McpIncidentObject $object): array
    {
        $incident = $object->incident;
        $watcher  = $object->watcher;

        return [
            'id'             => $incident->id,
            'status'         => $incident->status->value,
            'first_event_at' => $this->time($incident->firstEventAt),
            'last_event_at'  => $this->time($incident->lastEventAt),
            'events_count'   => $incident->eventsCount,
            'closed_at'      => $this->time($incident->closedAt),
            'watcher'        => is_null($watcher)
                ? null
                : [
                    'id'          => $watcher->id,
                    'name'        => $watcher->name,
                    'type'        => $watcher->type->value,
                    'service_ids' => $watcher->match->serviceIds ?? [],
                ],
        ];
    }

    public function traceNotFound(string $traceId): McpToolResult
    {
        return $this->error(
            error: 'trace_not_found',
            hint: "Trace [$traceId] not found. Take trace ids from this installation only."
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function service(?TraceServiceObject $service): ?array
    {
        return is_null($service)
            ? null
            : [
                'id'   => $service->id,
                'name' => $service->name,
            ];
    }

    /**
     * @return array<string, mixed>
     */
    public function treeNode(McpTraceTreeNodeObject $object): array
    {
        $node = [
            'trace_id'        => $object->node->traceId,
            'parent_trace_id' => $object->node->parentTraceId,
            'service'         => $this->service($object->service),
            'type'            => $object->node->type,
            'status'          => $object->node->status,
            'duration'        => $object->node->duration,
        ];

        if (!is_null($object->childrenCount)) {
            $node['children_count'] = $object->childrenCount;
        }

        return $node;
    }

    public function treeNotReady(McpTraceTreeObject $tree, string $buildingHint): ?McpToolResult
    {
        return match ($tree->status) {
            TraceTreeCacheStateStatusEnum::Finished => null,
            TraceTreeCacheStateStatusEnum::Failed,
            TraceTreeCacheStateStatusEnum::Canceled => $this->error(
                error: 'tree_' . $tree->status->value,
                hint: sprintf(
                    'Building the tree of root trace [%s] ended with status %s%s. '
                    . 'Only the user can rebuild it in the SLogger UI.',
                    $tree->rootTraceId,
                    $tree->status->value,
                    is_null($tree->error) ? '' : ": $tree->error"
                )
            ),
            default => new McpToolResult(
                data: [
                    'status'              => 'tree_building',
                    'root_trace_id'       => $tree->rootTraceId,
                    'retry_after_seconds' => 10,
                    'hint'                => $buildingHint,
                ]
            ),
        };
    }
}
