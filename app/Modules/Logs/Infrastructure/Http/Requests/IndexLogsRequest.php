<?php

namespace App\Modules\Logs\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexLogsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page'         => [
                'required',
                'integer',
                'min:1',
            ],
            'search_query' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'levels'       => [
                'sometimes',
                'nullable',
                'array',
            ],
            'levels.*'     => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }
}
