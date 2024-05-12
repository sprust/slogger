<?php

namespace App\Modules\TraceCollector\Framework\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TraceUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'traces'                            => [
                'required',
                'array',
                'min:1',
            ],
            'traces.*.trace_id'                 => [
                'required',
                'string',
                'min:20',
            ],
            'traces.*.status'                   => [
                'required',
                'string',
                'min:1',
                'max:40',
            ],
            'traces.*.profiling'                => [
                'sometimes',
                'array',
            ],
            'traces.*.profiling.*.raw'          => [
                'required',
                'string',
            ],
            'traces.*.profiling.*.calling'      => [
                'required',
                'string',
            ],
            'traces.*.profiling.*.callable'     => [
                'required',
                'string',
            ],
            'traces.*.profiling.*.data'         => [
                'required',
                'array',
            ],
            'traces.*.profiling.*.data.*.name'  => [
                'required',
                'string',
            ],
            'traces.*.profiling.*.data.*.value' => [
                'required',
                'numeric',
            ],
            'traces.*.tags'                     => [
                'sometimes',
                'array',
            ],
            'traces.*.tags.*'                   => [
                'required',
                'string',
            ],
            'traces.*.data'                     => [
                'sometimes',
                'json',
            ],
            'traces.*.duration'                 => [
                'present',
                'nullable',
                'numeric',
            ],
            'traces.*.memory'                   => [
                'sometimes',
                'numeric',
            ],
            'traces.*.cpu'                      => [
                'sometimes',
                'numeric',
            ],
        ];
    }
}
