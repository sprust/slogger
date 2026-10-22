<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'min:1', 'max:255'],
            'last_name'  => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
