<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'min:1', 'max:255'],
            'last_name'  => ['sometimes', 'nullable', 'string', 'max:255'],
            'email'      => ['required', 'string', 'email', 'max:255'],
            'password'   => ['required', 'string', 'min:8', 'max:10'],
        ];
    }
}
