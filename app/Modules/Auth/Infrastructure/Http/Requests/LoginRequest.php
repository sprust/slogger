<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
