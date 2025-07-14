<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TraceTreeContentRequest extends FormRequest
{
    function rules(): array
    {
        return [
            'trace_id' => [
                'required',
                'string',
            ],
            'is_child' => [
                'required',
                'boolean',
            ],
        ];
    }
}
