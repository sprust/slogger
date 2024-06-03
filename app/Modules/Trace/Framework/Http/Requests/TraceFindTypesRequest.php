<?php

namespace App\Modules\Trace\Framework\Http\Requests;

use App\Modules\Trace\Framework\Http\Services\RequestRules;
use Illuminate\Foundation\Http\FormRequest;

class TraceFindTypesRequest extends FormRequest
{
    public function rules(): array
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
            'text'          => [
                'sometimes',
                'string',
                'nullable',
                'min:1',
            ],
            'logging_from'  => [
                'sometimes',
                'date',
            ],
            'logging_to'    => [
                'sometimes',
                'date',
            ],
            ...RequestRules::filterData(),
            'has_profiling' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
