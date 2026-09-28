<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpDynamicIndexesAction;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolInterface;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolResult;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolSchema;
use App\Modules\Trace\Entities\DynamicIndex\TraceDynamicIndexFieldObject;
use App\Modules\Trace\Entities\DynamicIndex\TraceDynamicIndexObject;
use Illuminate\Support\Carbon;

readonly class ListDynamicIndexesTool implements McpToolInterface
{
    public function __construct(
        private FindMcpDynamicIndexesAction $findMcpDynamicIndexesAction,
        private McpToolFormatter $formatter
    ) {
    }

    public function name(): string
    {
        return 'list_dynamic_indexes';
    }

    public function title(): string
    {
        return 'List dynamic indexes';
    }

    public function description(): string
    {
        return 'Existing trace dynamic indexes, newest first: their fields, the hours they cover, status and '
            . 'until when they live. A query with the same set of filter fields over the same hours reuses '
            . 'an index instead of building a new one. No index needed.';
    }

    public function schema(): McpToolSchema
    {
        return new McpToolSchema();
    }

    public function call(McpToolArguments $arguments): McpToolResult
    {
        return new McpToolResult(
            data: [
                'indexes' => array_map(
                    fn(TraceDynamicIndexObject $index) => $this->index($index),
                    $this->findMcpDynamicIndexesAction->handle()
                ),
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function index(TraceDynamicIndexObject $index): array
    {
        $hours = array_values(
            array_filter(
                array_map(
                    fn(string $collectionName) => $this->collectionHour($collectionName),
                    $index->collectionNames
                )
            )
        );

        usort($hours, static fn(Carbon $a, Carbon $b): int => $a->getTimestamp() <=> $b->getTimestamp());

        return [
            'index_id'        => $index->id,
            'fields'          => array_map(
                static fn(TraceDynamicIndexFieldObject $field) => [
                    'name'  => $field->name,
                    'title' => $field->title,
                ],
                $index->fields
            ),
            'first_hour'      => $this->formatter->time($hours[0] ?? null),
            'last_hour'       => $this->formatter->time($hours[count($hours) - 1] ?? null),
            'status'          => match (true) {
                !is_null($index->error) => 'error',
                $index->inProcess       => 'building',
                default                 => 'ready',
            },
            'actual_until_at' => $this->formatter->time($index->actualUntilAt),
        ];
    }

    private function collectionHour(string $collectionName): ?Carbon
    {
        if (preg_match('/^traces_(\d{4}_\d{2}_\d{2})_(\d{2})_\d{2}$/', $collectionName, $matches) !== 1) {
            return null;
        }

        $hour = Carbon::createFromFormat('Y_m_d H', "$matches[1] $matches[2]", 'UTC');

        return $hour instanceof Carbon ? $hour->startOfHour() : null;
    }
}
