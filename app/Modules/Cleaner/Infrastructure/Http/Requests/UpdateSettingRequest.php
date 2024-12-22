<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'days_life_time' => [
                'required',
                'integer',
                'min:1',
            ],
            'only_data'      => [
                'required',
                'boolean',
            ],
        ];
    }
}
