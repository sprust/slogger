<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TraceTreeIndexRequest extends FormRequest
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
            'page'     => [
                'required',
                'integer',
                'min:1',
            ],
        ];
    }
}
