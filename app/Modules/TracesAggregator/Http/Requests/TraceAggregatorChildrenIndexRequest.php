<?php

namespace App\Modules\TracesAggregator\Http\Requests;

use App\Modules\TracesAggregator\Enums\TraceChildrenSortFieldEnum;
use App\Services\Enums\SortDirectionEnum;
use Illuminate\Foundation\Http\FormRequest;

class TraceAggregatorChildrenIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page'             => [
                'required',
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
            'logging_from'     => [
                'sometimes',
                'date',
            ],
            'logging_to'       => [
                'sometimes',
                'date',
            ],
            'sort'             => [
                'sometimes',
                'array',
            ],
            'sort.*.field'     => [
                'sometimes',
                'in:' . implode(
                    ',',
                    array_map(
                        fn(TraceChildrenSortFieldEnum $enum) => $enum->value,
                        TraceChildrenSortFieldEnum::cases()
                    )
                ),
            ],
            'sort.*.direction' => [
                'sometimes',
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
