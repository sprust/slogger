<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateMcpRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:255'],
        ];
    }
}
