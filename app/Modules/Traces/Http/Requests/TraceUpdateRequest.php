<?php

namespace App\Modules\Traces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TraceUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'traces'                                            => [
                'required',
                'array',
                'min:1',
            ],
            'traces.*.trace_id'                                 => [
                'required',
                'string',
                'min:20',
            ],
            'traces.*.profiling'                                => [
                'sometimes',
                'array',
            ],
            'traces.*.profiling.*.raw'                          => [
                'required',
                'string',
            ],
            'traces.*.profiling.*.calling'                      => [
                'required',
                'string',
            ],
            'traces.*.profiling.*.callable'                     => [
                'required',
                'string',
            ],
            'traces.*.profiling.*.data'                         => [
                'required',
                'array',
            ],
            'traces.*.profiling.*.data.number_of_calls'         => [
                'required',
                'int',
            ],
            'traces.*.profiling.*.data.wait_time_in_ms'         => [
                'required',
                'numeric',
            ],
            'traces.*.profiling.*.data.cpu_time'                => [
                'required',
                'numeric',
            ],
            'traces.*.profiling.*.data.memory_usage_in_bytes'   => [
                'required',
                'numeric',
            ],
            'traces.*.profiling.*.data.peak_memory_usage_in_mb' => [
                'required',
                'numeric',
            ],
            'traces.*.tags'                                     => [
                'sometimes',
                'array',
            ],
            'traces.*.tags.*'                                   => [
                'required',
                'string',
            ],
            'traces.*.data'                                     => [
                'sometimes',
                'json',
            ],
            'traces.*.duration'                                 => [
                'present',
                'nullable',
                'numeric',
            ],
            'traces.*.memory'                                   => [
                'sometimes',
                'numeric',
            ],
            'traces.*.cpu'                                      => [
                'sometimes',
                'numeric',
            ],
        ];
    }
}
