<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Requests;

use App\Modules\Trace\Entities\Trace\Tree\TraceTreeChildrenCursorObject;
use Illuminate\Foundation\Http\FormRequest;

class TraceTreeChildrenRequest extends FormRequest
{
    public const int DEFAULT_LIMIT = 200;

    public const int MAX_LIMIT = 1000;

    public function rules(): array
    {
        return [
            'root_trace_id'   => [
                'required',
                'string',
            ],
            'parent_trace_id' => [
                'present',
                'nullable',
                'string',
            ],
            'cursor'          => [
                'sometimes',
                'nullable',
                'string',
                'regex:' . TraceTreeChildrenCursorObject::PATTERN,
            ],
            'limit'           => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
                'max:' . self::MAX_LIMIT,
            ],
        ];
    }
}
