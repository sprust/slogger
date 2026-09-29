<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Bridges;

use App\Modules\Mcp\Domain\Services\McpTracePeriodMapper;
use App\Modules\Mcp\Entities\Bridges\McpTraceDataFieldObject;
use App\Modules\Mcp\Entities\Bridges\McpTraceDataFieldsObject;
use App\Modules\Mcp\Parameters\FindMcpTraceDataFieldsParameters;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceDetailAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTracesAction;
use App\Modules\Trace\Entities\Trace\Data\TraceDataObject;
use App\Modules\Trace\Parameters\TraceFindParameters;
use App\Services\Clickhouse\ClickhouseQueryException;

readonly class FindMcpTraceDataFieldsAction
{
    public const int TRACES_COUNT = 5;
    public const int FIELDS_LIMIT = 200;

    public function __construct(
        private FindTracesAction $findTracesAction,
        private FindTraceDetailAction $findTraceDetailAction,
        private McpTracePeriodMapper $periodMapper
    ) {
    }

    /**
     * @throws ClickhouseQueryException
     */
    public function handle(FindMcpTraceDataFieldsParameters $parameters): McpTraceDataFieldsObject
    {
        $traces = $this->findTracesAction->handle(
            new TraceFindParameters(
                perPage: self::TRACES_COUNT,
                serviceIds: $parameters->serviceIds,
                loggingPeriod: $this->periodMapper->toLoggingPeriod($parameters->period),
                types: [$parameters->type]
            )
        );

        /** @var array<string, McpTraceDataFieldObject> $fields */
        $fields = [];

        $tracesCount = 0;

        foreach ($traces->items as $item) {
            $detail = $this->findTraceDetailAction->handle($item->trace->traceId);

            if (is_null($detail)) {
                continue;
            }

            $tracesCount++;

            $this->collect($detail->data, $fields);
        }

        return new McpTraceDataFieldsObject(
            fields: array_slice(array_values($fields), 0, self::FIELDS_LIMIT),
            tracesCount: $tracesCount
        );
    }

    /**
     * @param array<string, McpTraceDataFieldObject> $fields
     */
    private function collect(TraceDataObject $data, array &$fields): void
    {
        $children = $data->children ?? [];

        if (count($children) === 0) {
            if ($data->key !== '' && !array_key_exists($data->key, $fields)) {
                $fields[$data->key] = new McpTraceDataFieldObject(
                    key: $data->key,
                    example: $data->value
                );
            }

            return;
        }

        foreach ($children as $child) {
            $this->collect($child, $fields);
        }
    }
}
