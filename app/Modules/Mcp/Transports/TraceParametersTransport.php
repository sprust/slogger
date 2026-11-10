<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Transports;

use App\Modules\Mcp\Parameters\Data\TraceDataFilterItemParameters;
use App\Modules\Mcp\Parameters\TraceTreeFilterParameters;
use App\Modules\Trace\Enums\TraceDataFilterCompNumericTypeEnum;
use App\Modules\Trace\Enums\TraceDataFilterCompStringTypeEnum;
use App\Modules\Trace\Parameters\Data\TraceDataFilterBooleanParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterItemParameters as SourceTraceDataFilterItemParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterNumericParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterStringParameters;
use App\Modules\Trace\Parameters\TraceTreeFilterParameters as SourceTraceTreeFilterParameters;

readonly class TraceParametersTransport
{
    /**
     * @param array<TraceDataFilterItemParameters> $filters
     *
     * @return array<SourceTraceDataFilterItemParameters>
     */
    public function toTraceDataFilters(array $filters): array
    {
        return array_map($this->toTraceDataFilter(...), $filters);
    }

    public function toTraceTreeFilterParameters(
        TraceTreeFilterParameters $traceTreeFilterParameters
    ): SourceTraceTreeFilterParameters {
        return new SourceTraceTreeFilterParameters(
            serviceIds: $traceTreeFilterParameters->serviceIds,
            types: $traceTreeFilterParameters->types,
            tags: $traceTreeFilterParameters->tags,
            statuses: $traceTreeFilterParameters->statuses,
        );
    }

    private function toTraceDataFilter(
        TraceDataFilterItemParameters $traceDataFilterItemParameters
    ): SourceTraceDataFilterItemParameters {
        return new SourceTraceDataFilterItemParameters(
            field: $traceDataFilterItemParameters->field,
            null: $traceDataFilterItemParameters->null,
            exists: $traceDataFilterItemParameters->exists,
            numeric: $traceDataFilterItemParameters->numeric === null
                ? null
                : new TraceDataFilterNumericParameters(
                    value: $traceDataFilterItemParameters->numeric->value,
                    comp: TraceDataFilterCompNumericTypeEnum::from(
                        $traceDataFilterItemParameters->numeric->comp->value
                    ),
                ),
            string: $traceDataFilterItemParameters->string === null
                ? null
                : new TraceDataFilterStringParameters(
                    value: $traceDataFilterItemParameters->string->value,
                    comp: TraceDataFilterCompStringTypeEnum::from(
                        $traceDataFilterItemParameters->string->comp->value
                    ),
                ),
            boolean: $traceDataFilterItemParameters->boolean === null
                ? null
                : new TraceDataFilterBooleanParameters(
                    value: $traceDataFilterItemParameters->boolean->value,
                ),
        );
    }
}
