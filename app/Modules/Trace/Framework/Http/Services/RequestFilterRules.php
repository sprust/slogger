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

    public static function loggedFromTo(): array
    {
        return [
            'logging_from' => [
                'sometimes',
                'date',
            ],
            ...static::loggedTo(),
        ];
    }

    public static function loggedTo(): array
    {
        return [
            'logging_to' => [
                'sometimes',
                'date',
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

    public static function types(): array
    {
        return [
            'types'   => [
                'sometimes',
                'array',
            ],
            'types.*' => [
                'required',
                'string',
            ],
        ];
    }

    public static function tags(): array
    {
        return [
            'tags'   => [
                'sometimes',
                'array',
            ],
            'tags.*' => [
                'required',
                'string',
            ],
        ];
    }

    public static function statuses(): array
    {
        return [
            'statuses'   => [
                'sometimes',
                'array',
            ],
            'statuses.*' => [
                'required',
                'string',
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

    public static function hasProfiling(): array
    {
        return [
            'has_profiling' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public static function durationFromTo(): array
    {
        return [
            'duration_from' => [
                'sometimes',
                'numeric',
                'nullable',
            ],
            'duration_to'   => [
                'sometimes',
                'numeric',
                'nullable',
            ],
        ];
    }

    public static function memoryFromTo(): array
    {
        return [
            'memory_from' => [
                'sometimes',
                'numeric',
                'nullable',
            ],
            'memory_to'   => [
                'sometimes',
                'numeric',
                'nullable',
            ],
        ];
    }

    public static function cpuFromTo(): array
    {
        return [
            'cpu_from' => [
                'sometimes',
                'numeric',
                'nullable',
            ],
            'cpu_to'   => [
                'sometimes',
                'numeric',
                'nullable',
            ],
        ];
    }
}
