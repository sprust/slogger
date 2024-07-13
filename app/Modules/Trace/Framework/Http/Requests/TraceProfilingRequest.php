<?php

namespace App\Modules\Trace\Framework\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TraceProfilingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'caller' => [
                'sometimes',
                'string',
                'min:1',
            ],
        ];
    }
}
