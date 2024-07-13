<?php

namespace App\Modules\Trace\Framework\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TraceProfilingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'caller'            => [
                'sometimes',
                'nullable',
                'string',
                'min:1',
            ],
            'excluded_callers'   => [
                'sometimes',
                'nullable',
                'array',
            ],
            'excluded_callers.*' => [
                'required',
                'string',
            ],
        ];
    }
}
