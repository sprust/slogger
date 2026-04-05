<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TraceTreeRootTraceStateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'root_trace_id' => [
                'required',
                'string',
            ],
        ];
    }
}
