<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * An incident open for a day can hold a few hundred events, and the first page is not all
 * of them.
 */
class IndexIncidentEventsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page'     => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:200'],
        ];
    }
}
