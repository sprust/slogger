<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Dto\Trace\Data;

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
