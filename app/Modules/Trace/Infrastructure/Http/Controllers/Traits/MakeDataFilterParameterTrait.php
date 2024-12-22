<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Controllers\Traits;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Trace\Enums\TraceDataFilterCompNumericTypeEnum;
use App\Modules\Trace\Enums\TraceDataFilterCompStringTypeEnum;
use App\Modules\Trace\Parameters\Data\TraceDataFilterBooleanParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterItemParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterNumericParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterStringParameters;

trait MakeDataFilterParameterTrait
{
    /**
     * @param array<string, mixed> $validated
     */
    protected function makeDataFilterParameter(array $validated): TraceDataFilterParameters
    {
        return new TraceDataFilterParameters(
            filter: array_map(
                fn(array $filterItem) => new TraceDataFilterItemParameters(
                    field: ArrayValueGetter::string($filterItem, 'field'),
                    null: ArrayValueGetter::boolNull($filterItem, 'null'),
                    numeric: array_key_exists('numeric', $filterItem)
                        ? new TraceDataFilterNumericParameters(
                            value: ArrayValueGetter::intFloat($filterItem['numeric'], 'value'),
                            comp: TraceDataFilterCompNumericTypeEnum::from(
                                ArrayValueGetter::string($filterItem['numeric'], 'comp')
                            ),
                        )
                        : null,
                    string: array_key_exists('string', $filterItem)
                        ? new TraceDataFilterStringParameters(
                            value: $filterItem['string']['value'],
                            comp: TraceDataFilterCompStringTypeEnum::from(
                                ArrayValueGetter::string($filterItem['string'], 'comp')
                            ),
                        )
                        : null,
                    boolean: array_key_exists('boolean', $filterItem)
                        ? new TraceDataFilterBooleanParameters(
                            ArrayValueGetter::bool($filterItem['boolean'], 'value')
                        )
                        : null
                ),
                $validated['data']['filter'] ?? []
            ),
            fields: ArrayValueGetter::arrayStringNull($validated['data'], 'fields') ?? [],
        );
    }
}
