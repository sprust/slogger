<?php

namespace App\Modules\Trace\Framework\Http\Requests;

use App\Modules\Common\Enums\SortDirectionEnum;
use App\Modules\Trace\Framework\Http\Services\RequestFilterRules;
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
            'sort'               => [
                'sometimes',
                'array',
            ],
            ...RequestFilterRules::hasProfiling(),
            'sort.*.field'       => [
                'required',
                'string',
            ],
            'sort.*.direction'   => [
                'required',
                'string',
                'in:' . implode(
                    ',',
                    array_map(
                        fn(SortDirectionEnum $enum) => $enum->value,
                        SortDirectionEnum::cases()
                    )
                ),
            ],
        ];
    }
}
