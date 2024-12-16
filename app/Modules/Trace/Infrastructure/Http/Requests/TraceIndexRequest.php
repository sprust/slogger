<?php

namespace App\Modules\Trace\Infrastructure\Http\Requests;

use App\Modules\Trace\Infrastructure\Http\Services\RequestFilterRules;
use Illuminate\Foundation\Http\FormRequest;

class TraceIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page'               => [
                'required',
                'int',
                'min:1',
            ],
            'per_page'           => [
                'sometimes',
                'int',
                'min:1',
            ],
            ...RequestFilterRules::services(),
            'trace_id'           => [
                'sometimes',
                'nullable',
                'string',
            ],
            'all_traces_in_tree' => [
                'sometimes',
                'boolean',
            ],
            ...RequestFilterRules::loggedFromTo(),
            ...RequestFilterRules::types(),
            ...RequestFilterRules::tags(),
            ...RequestFilterRules::statuses(),
            ...RequestFilterRules::durationFromTo(),
            ...RequestFilterRules::memoryFromTo(),
            ...RequestFilterRules::cpuFromTo(),
            ...RequestFilterRules::data(),
            'data.fields'        => [
                'sometimes',
                'array',
            ],
            'data.fields.*'      => [
                'required',
                'string',
            ],
            ...RequestFilterRules::hasProfiling(),
        ];
    }
}
