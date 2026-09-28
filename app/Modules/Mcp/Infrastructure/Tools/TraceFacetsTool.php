<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpTraceFacetsAction;
use App\Modules\Mcp\Domain\Actions\Queries\FindMcpSettingsAction;
use App\Modules\Mcp\Domain\Exceptions\McpTraceIndexBuildingException;
use App\Modules\Mcp\Domain\Exceptions\McpTraceIndexFailedException;
use App\Modules\Mcp\Entities\Bridges\McpTraceFacetListObject;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolInterface;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolProperty;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolPropertyTypeEnum;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolResult;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolSchema;
use App\Modules\Mcp\Parameters\FindMcpTraceFacetsParameters;
use App\Modules\Trace\Entities\Trace\TraceStringFieldObject;

readonly class TraceFacetsTool implements McpToolInterface
{
    private const int MAX_FILTER_VALUES = 20;

    public function __construct(
        private FindMcpTraceFacetsAction $findMcpTraceFacetsAction,
        private FindMcpSettingsAction $findMcpSettingsAction,
        private McpToolTraceScopeReader $scopeReader,
        private McpToolFormatter $formatter
    ) {
    }

    public function name(): string
    {
        return 'trace_facets';
    }

    public function title(): string
    {
        return 'Trace facets';
    }

    public function description(): string
    {
        return 'Trace types, statuses and tags of a service over a period with their counts, most frequent '
            . 'first, as the filters of the SLogger traces page. Types are counted without the types '
            . 'filter, statuses without the statuses filter. ' . McpToolTraceScopeReader::INDEX_NOTE;
    }

    public function schema(): McpToolSchema
    {
        return new McpToolSchema([
            ...$this->scopeReader->properties(),
            new McpToolProperty(
                name: 'types',
                type: McpToolPropertyTypeEnum::StringList,
                description: 'Trace types.',
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
        $scope = $this->scopeReader->read($arguments);

        if ($scope instanceof McpToolResult) {
            return $scope;
        }

        try {
            $facets = $this->findMcpTraceFacetsAction->handle(
                new FindMcpTraceFacetsParameters(
                    serviceIds: $scope->serviceIds,
                    period: $scope->period,
                    types: $arguments->stringList('types'),
                    statuses: $arguments->stringList('statuses'),
                    limit: $this->findMcpSettingsAction->handle()->facetsLimit
                )
            );
        } catch (McpTraceIndexBuildingException $exception) {
            return $this->formatter->indexBuilding($exception->indexId);
        } catch (McpTraceIndexFailedException $exception) {
            return $this->formatter->indexError($exception->getMessage());
        }

        return new McpToolResult(
            data: [
                ...$this->scopeReader->periodData($scope),
                'types'     => $this->items($facets->types),
                'statuses'  => $this->items($facets->statuses),
                'tags'      => $this->items($facets->tags),
                'truncated' => [
                    'types'    => $facets->types->truncated,
                    'statuses' => $facets->statuses->truncated,
                    'tags'     => $facets->tags->truncated,
                ],
            ]
        );
    }

    /**
     * @return array<int, array<string, string|int>>
     */
    private function items(McpTraceFacetListObject $list): array
    {
        return array_map(
            static fn(TraceStringFieldObject $item) => [
                'value' => $item->name,
                'count' => $item->count,
            ],
            $list->items
        );
    }
}
