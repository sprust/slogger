<?php

namespace App\Modules\Trace\Framework\Http\Controllers\Traits;

use App\Modules\Trace\Domain\Entities\Parameters\Data\TraceDataFilterBooleanParameters;
use App\Modules\Trace\Domain\Entities\Parameters\Data\TraceDataFilterItemParameters;
use App\Modules\Trace\Domain\Entities\Parameters\Data\TraceDataFilterNumericParameters;
use App\Modules\Trace\Domain\Entities\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Domain\Entities\Parameters\Data\TraceDataFilterStringParameters;
use App\Modules\Trace\Enums\TraceDataFilterCompNumericTypeEnum;
use App\Modules\Trace\Enums\TraceDataFilterCompStringTypeEnum;

trait MakeDataFilterParameterTrait
{
    protected function makeDataFilterParameter(array $validated): TraceDataFilterParameters
    {
        return new TraceDataFilterParameters(
            filter: array_map(
                fn(array $filterItem) => new TraceDataFilterItemParameters(
                    field: $filterItem['field'],
                    null: array_key_exists('null', $filterItem)
                        ? $filterItem['null']
                        : null,
                    numeric: array_key_exists('numeric', $filterItem)
                        ? new TraceDataFilterNumericParameters(
                            value: $filterItem['numeric']['value'],
                            comp: TraceDataFilterCompNumericTypeEnum::from($filterItem['numeric']['comp']),
                        )
                        : null,
                    string: array_key_exists('string', $filterItem)
                        ? new TraceDataFilterStringParameters(
                            value: $filterItem['string']['value'],
                            comp: TraceDataFilterCompStringTypeEnum::from($filterItem['string']['comp']),
                        )
                        : null,
                    boolean: array_key_exists('boolean', $filterItem)
                        ? new TraceDataFilterBooleanParameters(
                            value: $filterItem['boolean']['value']
                        )
                        : null
                ),
                $validated['data']['filter'] ?? []
            ),
            fields: $validated['data']['fields'] ?? [],
        );
    }
}
