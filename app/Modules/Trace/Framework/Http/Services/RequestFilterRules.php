<?php

namespace App\Modules\Trace\Framework\Http\Services;

use App\Modules\Trace\Enums\TraceDataFilterCompNumericTypeEnum;
use App\Modules\Trace\Enums\TraceDataFilterCompStringTypeEnum;

class RequestFilterRules
{
    public static function services(): array
    {
        return [
            'service_ids'   => [
                'sometimes',
                'array',
            ],
            'service_ids.*' => [
                'required',
                'integer',
            ],
        ];
    }

    public static function text(): array
    {
        return [
            'text' => [
                'sometimes',
                'string',
                'nullable',
                'min:1',
            ],
        ];
    }

    public static function data(): array
    {
        return [
            'data'                        => [
                'sometimes',
                'array',
            ],
            'data.filter'                 => [
                'sometimes',
                'array',
            ],
            'data.filter.*.field'         => [
                'required',
                'string',
            ],
            'data.filter.*.null'          => [
                'sometimes',
                'bool',
            ],
            'data.filter.*.numeric'       => [
                'sometimes',
                'array',
            ],
            "data.filter.*.numeric.value" => [
                'sometimes',
                'numeric',
            ],
            'data.filter.*.numeric.comp'  => [
                'sometimes',
                'string',
                'in:' . implode(
                    ',',
                    array_map(
                        fn(TraceDataFilterCompNumericTypeEnum $enum) => $enum->value,
                        TraceDataFilterCompNumericTypeEnum::cases()
                    )
                ),
            ],
            'data.filter.*.string'        => [
                'sometimes',
                'array',
            ],
            'data.filter.*.string.value'  => [
                'sometimes',
                'string',
            ],
            'data.filter.*.string.comp'   => [
                'sometimes',
                'string',
                'in:' . implode(
                    ',',
                    array_map(
                        fn(TraceDataFilterCompStringTypeEnum $enum) => $enum->value,
                        TraceDataFilterCompStringTypeEnum::cases()
                    )
                ),
            ],
            'data.filter.*.boolean'       => [
                'sometimes',
                'array',
            ],
            'data.filter.*.boolean.value' => [
                'sometimes',
                'bool',
            ],
        ];
    }
}
