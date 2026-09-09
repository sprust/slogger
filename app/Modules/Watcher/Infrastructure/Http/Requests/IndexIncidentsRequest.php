<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Requests;

use App\Modules\Watcher\Enums\WatcherIncidentStatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class IndexIncidentsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page'       => ['sometimes', 'integer', 'min:1'],
            'per_page'   => ['sometimes', 'integer', 'min:1', 'max:200'],
            'status'     => [
                'sometimes',
                'nullable',
                'string',
                'in:' . implode(
                    ',',
                    array_map(
                        fn(WatcherIncidentStatusEnum $status): string => $status->value,
                        WatcherIncidentStatusEnum::cases()
                    )
                ),
            ],
            'watcher_id' => ['sometimes', 'nullable', 'integer'],
        ];
    }
}
