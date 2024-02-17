<?php

namespace App\Modules\TracesAggregator\Http\Controllers;

use App\Modules\TracesAggregator\Dto\Parameters\DataFilter\TraceDataFilterBooleanParameters;
use App\Modules\TracesAggregator\Dto\Parameters\DataFilter\TraceDataFilterItemParameters;
use App\Modules\TracesAggregator\Dto\Parameters\DataFilter\TraceDataFilterNumericParameters;
use App\Modules\TracesAggregator\Dto\Parameters\DataFilter\TraceDataFilterParameters;
use App\Modules\TracesAggregator\Dto\Parameters\DataFilter\TraceDataFilterStringParameters;
use App\Modules\TracesAggregator\Enums\TraceDataFilterCompNumericTypeEnum;
use App\Modules\TracesAggregator\Enums\TraceDataFilterCompStringTypeEnum;

trait TraceAggregatorParentsControllerTrait
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
