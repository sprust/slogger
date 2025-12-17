<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TraceTreeTreeRequest extends FormRequest
{
    function rules(): array
    {
        return [
            'trace_id' => [
                'required',
                'string',
            ],
            'fresh'    => [
                'required',
                'boolean',
            ],
            'is_child' => [
                'required',
                'boolean',
            ],
        ];
    }
}
