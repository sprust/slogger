<?php

namespace App\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthLoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email'    => [
                'required',
                'string',
                'email:strict',
            ],
            'password' => [
                'required',
                'string',
                'min:5',
                'max:50',
            ],
        ];
    }
}
