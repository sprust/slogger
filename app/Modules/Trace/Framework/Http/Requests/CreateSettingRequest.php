<?php

namespace App\Modules\Trace\Framework\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'days_life_time' => [
                'required',
                'integer',
                'min:1',
            ],
            'type'           => [
                'present',
                'string',
                'nullable',
            ],
        ];
    }
}
