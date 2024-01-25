<?php

namespace App\Modules\Traces\Http\Requests;

use App\Modules\Traces\Enums\TraceTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class TraceCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'traces'                 => [
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
                new Enum(TraceTypeEnum::class),
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
            'traces.*.logged_at'       => [
                'required',
                'numeric',
            ],
        ];
    }
}
