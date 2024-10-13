<?php

namespace App\Modules\Trace\Transports;

use App\Modules\Trace\Parameters\Data\TraceDataFilterItemParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Repositories\Dto\Data\TraceDataFilterBooleanDto;
use App\Modules\Trace\Repositories\Dto\Data\TraceDataFilterDto;
use App\Modules\Trace\Repositories\Dto\Data\TraceDataFilterItemDto;
use App\Modules\Trace\Repositories\Dto\Data\TraceDataFilterNumericDto;
use App\Modules\Trace\Repositories\Dto\Data\TraceDataFilterStringDto;

class TraceDataFilterTransport
{
    public static function toDtoIfNotNull(?TraceDataFilterParameters $parameters): ?TraceDataFilterDto
    {
        if (is_null($parameters)) {
            return null;
        }

        return new TraceDataFilterDto(
            filter: array_map(
                fn(TraceDataFilterItemParameters $dataFilterItem) => new TraceDataFilterItemDto(
                    field: $dataFilterItem->field,
                    null: $dataFilterItem->null,
                    numeric: $dataFilterItem->numeric
                        ? new TraceDataFilterNumericDto(
                            value: $dataFilterItem->numeric->value,
                            comp: $dataFilterItem->numeric->comp,
                        )
                        : null,
                    string: $dataFilterItem->string
                        ? new TraceDataFilterStringDto(
                            value: $dataFilterItem->string->value,
                            comp: $dataFilterItem->string->comp,
                        )
                        : null,
                    boolean: $dataFilterItem->boolean
                        ? new TraceDataFilterBooleanDto(
                            value: $dataFilterItem->boolean->value,
                        )
                        : null,
                ),
                $parameters->filter
            ),
            fields: $parameters->fields
        );
    }
}
