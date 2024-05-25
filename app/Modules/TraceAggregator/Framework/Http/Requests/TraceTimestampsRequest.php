<?php

namespace App\Modules\TraceAggregator\Framework\Http\Requests;

use App\Models\Services\Service;
use App\Modules\TraceAggregator\Enums\TraceDataFilterCompNumericTypeEnum;
use App\Modules\TraceAggregator\Enums\TraceDataFilterCompStringTypeEnum;
use App\Modules\TraceAggregator\Enums\TraceTimestampEnum;
use App\Modules\TraceAggregator\Enums\TraceTimestampPeriodEnum;
use Illuminate\Foundation\Http\FormRequest;

class TraceTimestampsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'timestamp_period'            => [
                'required',
                'string',
                'in:' . implode(
                    ',',
                    array_map(
                        fn(TraceTimestampPeriodEnum $enum) => $enum->value,
                        TraceTimestampPeriodEnum::cases()
                    )
                ),
            ],
            'timestamp_step'            => [
                'required',
                'string',
                'in:' . implode(
                    ',',
                    array_map(
                        fn(TraceTimestampEnum $enum) => $enum->value,
                        TraceTimestampEnum::cases()
                    )
                ),
            ],
            'service_ids'                 => [
                'sometimes',
                'array',
                'exists:' . Service::class . ',id',
            ],
            'service_ids.*'               => [
                'required',
                'integer',
            ],
            'logging_to'                  => [
                'sometimes',
                'date',
            ],
            'types'                       => [
                'sometimes',
                'array',
            ],
            'types.*'                     => [
                'required',
                'string',
            ],
            'tags'                        => [
                'sometimes',
                'array',
            ],
            'tags.*'                      => [
                'required',
                'string',
            ],
            'statuses'                    => [
                'sometimes',
                'array',
            ],
            'statuses.*'                  => [
                'required',
                'string',
            ],
            'duration_from'               => [
                'sometimes',
                'numeric',
                'nullable',
            ],
            'duration_to'                 => [
                'sometimes',
                'numeric',
                'nullable',
            ],
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
            'data.filter.*.numeric.value' => [
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
            'data.fields'                 => [
                'sometimes',
                'array',
            ],
            'data.fields.*'               => [
                'required',
                'string',
            ],
            'has_profiling'               => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
