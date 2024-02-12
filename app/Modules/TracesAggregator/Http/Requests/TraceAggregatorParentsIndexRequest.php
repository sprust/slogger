<?php

namespace App\Modules\TracesAggregator\Http\Requests;

use App\Services\Enums\SortDirectionEnum;
use Illuminate\Foundation\Http\FormRequest;

class TraceAggregatorParentsIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page'             => [
                'required',
                'int',
                'min:1',
            ],
            'per_page'         => [
                'sometimes',
                'int',
                'min:1',
            ],
            'types'            => [
                'sometimes',
                'array',
            ],
            'types.*'          => [
                'required',
                'string',
            ],
            'tags'             => [
                'sometimes',
                'array',
            ],
            'tags.*'           => [
                'required',
                'string',
            ],
            'logging_from'     => [
                'sometimes',
                'date',
            ],
            'logging_to'       => [
                'sometimes',
                'date',
            ],
            'custom_fields'       => [
                'sometimes',
                'array',
            ],
            'custom_fields.*'       => [
                'required',
                'string',
            ],
            'sort'             => [
                'sometimes',
                'array',
            ],
            'sort.*.field'     => [
                'required',
                'string',
            ],
            'sort.*.direction' => [
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
