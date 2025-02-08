<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Requests;

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
            'traces.*.status'          => [
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
                'string',
                'json',
            ],
            'traces.*.duration'        => [
                'present',
                'nullable',
                'numeric',
            ],
            'traces.*.memory'          => [
                'sometimes',
                'nullable',
                'numeric',
            ],
            'traces.*.cpu'             => [
                'sometimes',
                'nullable',
                'numeric',
            ],
            'traces.*.is_parent'       => [ // TODO: required after release
                'sometimes',
                'bool',
            ],
            'traces.*.logged_at'       => [
                'required',
                'numeric',
            ],
        ];
    }
}
