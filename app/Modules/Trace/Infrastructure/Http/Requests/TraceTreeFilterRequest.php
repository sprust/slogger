<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Requests;

use App\Modules\Trace\Infrastructure\Http\Services\RequestFilterRules;
use Illuminate\Foundation\Http\FormRequest;

class TraceTreeFilterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'root_trace_id' => [
                'required',
                'string',
            ],
            ...RequestFilterRules::services(),
            ...RequestFilterRules::types(),
            ...RequestFilterRules::tags(),
            ...RequestFilterRules::statuses(),
        ];
    }
}
