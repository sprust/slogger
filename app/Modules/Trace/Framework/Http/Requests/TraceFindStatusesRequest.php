<?php

namespace App\Modules\Trace\Framework\Http\Requests;

use App\Modules\Trace\Framework\Http\Services\RequestFilterRules;
use Illuminate\Foundation\Http\FormRequest;

class TraceFindStatusesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            ...RequestFilterRules::services(),
            ...RequestFilterRules::text(),
            'types'         => [
                'sometimes',
                'array',
            ],
            'types.*'       => [
                'required',
                'string',
            ],
            'tags'          => [
                'sometimes',
                'array',
            ],
            'tags.*'        => [
                'required',
                'string',
            ],
            'logging_from'  => [
                'sometimes',
                'date',
            ],
            'logging_to'    => [
                'sometimes',
                'date',
            ],
            ...RequestFilterRules::data(),
            'has_profiling' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
