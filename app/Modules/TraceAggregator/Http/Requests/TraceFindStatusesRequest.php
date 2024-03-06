<?php

namespace App\Modules\TraceAggregator\Http\Requests;

use App\Models\Services\Service;
use App\Modules\TraceAggregator\Enums\TraceDataFilterCompNumericTypeEnum;
use App\Modules\TraceAggregator\Enums\TraceDataFilterCompStringTypeEnum;
use Illuminate\Foundation\Http\FormRequest;

class TraceFindStatusesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'service_ids'                 => [
                'sometimes',
                'array',
                'exists:' . Service::class . ',id',
            ],
            'service_ids.*'               => [
                'required',
                'integer',
            ],
            'text'                        => [
                'sometimes',
                'string',
                'nullable',
                'min:1',
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
            'logging_from'                => [
                'sometimes',
                'date',
            ],
            'logging_to'                  => [
                'sometimes',
                'date',
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
        ];
    }
}
