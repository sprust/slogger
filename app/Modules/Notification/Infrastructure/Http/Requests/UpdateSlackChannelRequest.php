<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSlackChannelRequest extends FormRequest
{
    use ChannelRulesTrait;

    public function rules(): array
    {
        return [
            ...$this->commonRules(),
            'settings'             => ['required', 'array'],
            'settings.webhook_url' => ['nullable', 'string', 'min:10', 'max:500', 'url'],
        ];
    }
}
