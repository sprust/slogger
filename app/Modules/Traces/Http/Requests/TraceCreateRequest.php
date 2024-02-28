<?php

namespace App\Modules\Traces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TraceCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'traces'                   => [
                'required',
                'array',
                'min:1',
            ],
            'traces.*.trace_id'        => [
                'required',
                'string',
                'min:20',
            ],
            'traces.*.parent_trace_id' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'traces.*.type'            => [
                'required',
                'string',
                'min:1',
                'max:40',
            ],
            'traces.*.tags'            => [
                'sometimes',
                'array',
            ],
            'traces.*.tags.*'          => [
                'required',
                'string',
            ],
            'traces.*.data'            => [
                'required',
                'json',
            ],
            'traces.*.duration'        => [
                'present',
                'nullable',
                'numeric',
            ],
            'traces.*.memory'          => [
                'sometimes',
                'numeric',
            ],
            'traces.*.cpu'             => [
                'sometimes',
                'numeric',
            ],
            'traces.*.logged_at'       => [
                'required',
                'numeric',
            ],
        ];
    }
}
