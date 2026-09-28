<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Domain\Actions\Bridges\CompareMcpTraceGroupsAction;
use App\Modules\Mcp\Domain\Exceptions\McpTraceGroupByInvalidException;
use App\Modules\Mcp\Domain\Exceptions\McpTraceGroupsOverlapException;
use App\Modules\Mcp\Domain\Exceptions\McpTraceIndexBuildingException;
use App\Modules\Mcp\Domain\Exceptions\McpTraceIndexFailedException;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolInterface;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolProperty;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolPropertyTypeEnum;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolResult;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolSchema;
use App\Modules\Mcp\Parameters\CompareMcpTraceGroupsParameters;
use App\Modules\Trace\Entities\Trace\Groups\TraceGroupComparisonRowObject;

readonly class CompareTraceGroupsTool implements McpToolInterface
{
    private const int MAX_FILTER_VALUES = 20;

    public function __construct(
        private CompareMcpTraceGroupsAction $compareMcpTraceGroupsAction,
        private McpToolTraceScopeReader $scopeReader,
        private McpToolServiceFinder $serviceFinder,
        private McpToolFormatter $formatter
    ) {
    }

    public function name(): string
    {
        return 'compare_trace_groups';
    }

    public function title(): string
    {
        return 'Compare trace groups';
    }

    public function description(): string
    {
        return sprintf(
            'Compares two groups of traces over a period by status (group A, and group B or all other '
            . 'statuses) across the values of type, service, tag or a data key (data.<key>): counts and '
            . 'shares in each group, the values most typical of group A first, at most %d. Answers what '
            . 'failed traces have in common that the others do not. ',
            CompareMcpTraceGroupsAction::LIMIT
        ) . McpToolTraceScopeReader::INDEX_NOTE;
    }

    public function schema(): McpToolSchema
    {
        return new McpToolSchema([
            ...$this->scopeReader->properties(),
            new McpToolProperty(
                name: 'group_a_statuses',
                type: McpToolPropertyTypeEnum::StringList,
                description: 'Statuses of group A, for example failed.',
                required: true,
                min: 1,
                max: self::MAX_FILTER_VALUES
            ),
            new McpToolProperty(
                name: 'group_b_statuses',
                type: McpToolPropertyTypeEnum::StringList,
                description: 'Statuses of group B. All other statuses when omitted.',
                max: self::MAX_FILTER_VALUES
            ),
            new McpToolProperty(
                name: 'by',
                type: McpToolPropertyTypeEnum::String,
                description: 'type, service, tag or data.<key>, for example data.response.status.',
                required: true,
                max: 255
            ),
            new McpToolProperty(
                name: 'types',
                type: McpToolPropertyTypeEnum::StringList,
                description: 'Trace types.',
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

        $by = $arguments->string('by');

        try {
            $comparison = $this->compareMcpTraceGroupsAction->handle(
                new CompareMcpTraceGroupsParameters(
                    serviceIds: $scope->serviceIds,
                    period: $scope->period,
                    groupAStatuses: $arguments->stringList('group_a_statuses'),
                    groupBStatuses: $arguments->stringList('group_b_statuses'),
                    by: $by,
                    types: $arguments->stringList('types')
                )
            );
        } catch (McpTraceGroupByInvalidException $exception) {
            return $this->formatter->error(
                error: 'invalid_group_by',
                hint: $exception->getMessage() . ' Use type, service, tag or data.<key>.'
            );
        } catch (McpTraceGroupsOverlapException $exception) {
            return $this->formatter->error(
                error: 'overlapping_groups',
                hint: sprintf('Statuses [%s] are in both groups.', implode(', ', $exception->statuses))
            );
        } catch (McpTraceIndexBuildingException $exception) {
            return $this->formatter->indexBuilding($exception->indexId);
        } catch (McpTraceIndexFailedException $exception) {
            return $this->formatter->indexError($exception->getMessage());
        }

        $names = $this->serviceFinder->names();

        return new McpToolResult(
            data: [
                ...$this->scopeReader->periodData($scope),
                'by'            => $by,
                'group_a_total' => $comparison->groupATotal,
                'group_b_total' => $comparison->groupBTotal,
                'rows'          => array_map(
                    static fn(TraceGroupComparisonRowObject $row) => [
                        'value'         => $by === 'service' && is_numeric($row->value)
                            ? $names->describe((int) $row->value)
                            : $row->value,
                        'group_a_count' => $row->groupACount,
                        'group_a_share' => $row->groupAShare,
                        'group_b_count' => $row->groupBCount,
                        'group_b_share' => $row->groupBShare,
                    ],
                    $comparison->rows
                ),
                'truncated'     => $comparison->truncated,
            ]
        );
    }
}
