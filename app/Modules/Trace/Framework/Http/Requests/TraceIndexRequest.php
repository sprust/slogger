<?php

namespace App\Modules\Trace\Framework\Http\Requests;

use App\Models\Services\Service;
use App\Modules\Common\Enums\SortDirectionEnum;
use App\Modules\Trace\Enums\TraceDataFilterCompNumericTypeEnum;
use App\Modules\Trace\Enums\TraceDataFilterCompStringTypeEnum;
use Illuminate\Foundation\Http\FormRequest;

class TraceIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page'                        => [
                'required',
                'int',
                'min:1',
            ],
            'per_page'                    => [
                'sometimes',
                'int',
                'min:1',
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
            'trace_id'                    => [
                'sometimes',
                'nullable',
                'string',
            ],
            'all_traces_in_tree'          => [
                'sometimes',
                'boolean',
            ],
            'logging_from'                => [
                'sometimes',
                'date',
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
            'sort'                        => [
                'sometimes',
                'array',
            ],
            'has_profiling'               => [
                'sometimes',
                'boolean',
            ],
            'sort.*.field'                => [
                'required',
                'string',
            ],
            'sort.*.direction'            => [
                'required',
                'string',
                'in:' . implode(
                    ',',
                    array_map(
                        fn(SortDirectionEnum $enum) => $enum->value,
                        SortDirectionEnum::cases()
                    )
                ),
            ],
        ];
    }
}
