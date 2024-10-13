<?php

namespace App\Modules\Trace\Repositories\Dto\Trace\Data;

use App\Modules\Trace\Enums\TraceMetricFieldAggregatorEnum;

readonly class TraceMetricDataFieldsFilterDto
{
    /**
     * @param TraceMetricFieldAggregatorEnum[] $aggregations
     */
    public function __construct(
        public string $field,
        public array $aggregations,
    ) {
    }
}
