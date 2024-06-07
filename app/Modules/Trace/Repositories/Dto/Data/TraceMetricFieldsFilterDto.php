<?php

namespace App\Modules\Trace\Repositories\Dto\Data;

use App\Modules\Trace\Enums\TraceMetricFieldAggregatorEnum;
use App\Modules\Trace\Enums\TraceMetricFieldEnum;

readonly class TraceMetricFieldsFilterDto
{
    /**
     * @param TraceMetricFieldAggregatorEnum[] $aggregations
     */
    public function __construct(
        public TraceMetricFieldEnum $field,
        public array $aggregations,
    ) {
    }
}
