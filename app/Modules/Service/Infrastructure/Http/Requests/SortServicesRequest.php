<?php

declare(strict_types=1);

namespace App\Modules\Service\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SortServicesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'ids'   => ['present', 'array'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ];
    }
}
