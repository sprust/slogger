<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TraceAdminStoreCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title'         => [
                'required',
                'string',
                'min:1',
                'max:2000',
            ],
            'store_version' => [
                'required',
                'integer',
                'min:1',
            ],
            'store_data'    => [
                'required',
                'string',
            ],
            'auto'          => [
                'required',
                'boolean',
            ],
        ];
    }
}
