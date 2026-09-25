<?php

declare(strict_types=1);

namespace App\Modules\Logs\Infrastructure\Http\Requests;

use App\Modules\Logs\Enums\LogCursorDirectionEnum;
use Illuminate\Foundation\Http\FormRequest;

class FindLogEntriesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'files'        => [
                'required',
                'array',
                'min:1',
                'max:' . (int) config('module-logs.search.max_files'),
            ],
            'files.*'      => [
                'required',
                'string',
                'min:40',
                'max:40',
            ],
            'levels'       => [
                'sometimes',
                'nullable',
                'array',
            ],
            'levels.*'     => [
                'required',
                'string',
                'min:3',
                'max:64',
            ],
            'from'         => [
                'sometimes',
                'nullable',
                'date',
            ],
            'to'           => [
                'sometimes',
                'nullable',
                'date',
            ],
            'search_query' => [
                'sometimes',
                'nullable',
                'string',
                'min:1',
                'max:255',
            ],
            'cursor'       => [
                'sometimes',
                'nullable',
                'string',
                'min:1',
                'max:65535',
            ],
            'direction'    => [
                'sometimes',
                'nullable',
                'string',
                'min:1',
                'max:16',
                'in:' . implode(
                    ',',
                    array_map(
                        fn(LogCursorDirectionEnum $direction): string => $direction->value,
                        LogCursorDirectionEnum::cases()
                    )
                ),
            ],
            'per_page'     => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
                'max:' . (int) config('module-logs.search.max_per_page'),
            ],
        ];
    }
}
