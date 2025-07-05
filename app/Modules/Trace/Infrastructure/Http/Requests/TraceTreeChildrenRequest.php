<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TraceTreeChildrenRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => [
                'required',
                'integer',
                'min:1',
            ],
            'root' => [
                'required',
                'boolean',
            ],
            'traceId' => [
                'required',
                'string',
            ],
        ];
    }
}
