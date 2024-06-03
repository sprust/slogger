<?php

namespace App\Modules\Trace\Framework\Http\Requests;

use App\Models\Services\Service;
use App\Modules\Trace\Enums\TraceTimestampEnum;
use App\Modules\Trace\Enums\TraceTimestampPeriodEnum;
use App\Modules\Trace\Framework\Http\Services\RequestRules;
use Illuminate\Foundation\Http\FormRequest;

class TraceTimestampsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'timestamp_period' => [
                'required',
                'string',
                'in:' . implode(
                    ',',
                    array_map(
                        fn(TraceTimestampPeriodEnum $enum) => $enum->value,
                        TraceTimestampPeriodEnum::cases()
                    )
                ),
            ],
            'timestamp_step'   => [
                'required',
                'string',
                'in:' . implode(
                    ',',
                    array_map(
                        fn(TraceTimestampEnum $enum) => $enum->value,
                        TraceTimestampEnum::cases()
                    )
                ),
            ],
            'service_ids'      => [
                'sometimes',
                'array',
                'exists:' . Service::class . ',id',
            ],
            'service_ids.*'    => [
                'required',
                'integer',
            ],
            'logging_to'       => [
                'sometimes',
                'date',
            ],
            'types'            => [
                'sometimes',
                'array',
            ],
            'types.*'          => [
                'required',
                'string',
            ],
            'tags'             => [
                'sometimes',
                'array',
            ],
            'tags.*'           => [
                'required',
                'string',
            ],
            'statuses'         => [
                'sometimes',
                'array',
            ],
            'statuses.*'       => [
                'required',
                'string',
            ],
            'duration_from'    => [
                'sometimes',
                'numeric',
                'nullable',
            ],
            'duration_to'      => [
                'sometimes',
                'numeric',
                'nullable',
            ],
            ...RequestRules::filterData(),
            'has_profiling'    => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
