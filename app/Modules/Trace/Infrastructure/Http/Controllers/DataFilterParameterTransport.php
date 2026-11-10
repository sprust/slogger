<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Trace\Enums\TraceDataFilterCompNumericTypeEnum;
use App\Modules\Trace\Enums\TraceDataFilterCompStringTypeEnum;
use App\Modules\Trace\Parameters\Data\TraceDataFilterBooleanParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterItemParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterNumericParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterStringParameters;

class DataFilterParameterTransport
{
    /**
     * @param array<string, mixed> $validated
     */
    public function make(array $validated): TraceDataFilterParameters
    {
        // `data` is optional in every request that reaches here — RequestFilterRules::data()
        // says `sometimes` — so a caller that filters on nothing omits the key entirely.
        // Read it once as the empty filter it means; reaching into $validated['data'] on
        // such a request answered a search with a 500.
        $data = is_array($validated['data'] ?? null) ? $validated['data'] : [];

        return new TraceDataFilterParameters(
            filter: array_map(
                static fn(array $filterItem) => new TraceDataFilterItemParameters(
                    field: ArrayValueGetter::string(
                        data: $filterItem,
                        key: 'field',
                    ),
                    null: ArrayValueGetter::boolNull(
                        data: $filterItem,
                        key: 'null',
                    ),
                    exists: ArrayValueGetter::boolNull(
                        data: $filterItem,
                        key: 'exists',
                    ),
                    numeric: array_key_exists('numeric', $filterItem)
                        ? new TraceDataFilterNumericParameters(
                            value: ArrayValueGetter::intFloat(
                                data: $filterItem['numeric'],
                                key: 'value',
                            ),
                            comp: TraceDataFilterCompNumericTypeEnum::from(
                                ArrayValueGetter::string(
                                    data: $filterItem['numeric'],
                                    key: 'comp',
                                )
                            ),
                        )
                        : null,
                    string: array_key_exists('string', $filterItem)
                        ? new TraceDataFilterStringParameters(
                            value: $filterItem['string']['value'],
                            comp: TraceDataFilterCompStringTypeEnum::from(
                                ArrayValueGetter::string(
                                    data: $filterItem['string'],
                                    key: 'comp',
                                )
                            ),
                        )
                        : null,
                    boolean: array_key_exists('boolean', $filterItem)
                        ? new TraceDataFilterBooleanParameters(
                            value: ArrayValueGetter::bool(
                                data: $filterItem['boolean'],
                                key: 'value',
                            ),
                        )
                        : null,
                ),
                is_array($data['filter'] ?? null) ? $data['filter'] : []
            ),
            fields: ArrayValueGetter::arrayStringNull(
                data: $data,
                key: 'fields',
            ) ?? [],
        );
    }
}
