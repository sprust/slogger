<?php

namespace App\Modules\Traces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TraceUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'traces'            => [
                'required',
                'array',
                'min:1',
            ],
            'traces.*.trace_id' => [
                'required',
                'string',
                'min:20',
            ],
            'traces.*.tags'     => [
                'sometimes',
                'array',
            ],
            'traces.*.tags.*'   => [
                'required',
                'string',
            ],
            'traces.*.data'     => [
                'sometimes',
                'json',
            ],
        ];
    }
}
