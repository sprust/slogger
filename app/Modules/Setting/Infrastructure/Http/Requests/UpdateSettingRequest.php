<?php

declare(strict_types=1);

namespace App\Modules\Setting\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'value' => ['required', 'integer'],
        ];
    }
}
