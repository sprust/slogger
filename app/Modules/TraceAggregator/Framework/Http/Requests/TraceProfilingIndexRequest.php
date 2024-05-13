<?php

namespace App\Modules\TraceAggregator\Framework\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TraceProfilingIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'call' => [
                'sometimes',
                'string',
            ],
        ];
    }
}
