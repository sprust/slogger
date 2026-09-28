<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Exceptions;

use App\Modules\Trace\Enums\TraceTimestampEnum;
use App\Modules\Trace\Enums\TraceTimestampPeriodEnum;
use Exception;

class McpTraceMetricsStepNotAllowedException extends Exception
{
    /**
     * @param TraceTimestampEnum[] $allowedSteps
     */
    public function __construct(
        public readonly TraceTimestampPeriodEnum $period,
        public readonly TraceTimestampEnum $step,
        public readonly array $allowedSteps
    ) {
        parent::__construct("Step [$step->value] is not allowed for period [$period->value]");
    }
}
