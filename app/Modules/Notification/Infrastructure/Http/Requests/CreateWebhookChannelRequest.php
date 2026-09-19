<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateWebhookChannelRequest extends FormRequest
{
    use ChannelRulesTrait;

    public function rules(): array
    {
        return [
            ...$this->commonRules(),
            'settings'       => ['required', 'array'],
            'settings.url'   => ['required', 'string', 'min:10', 'max:500', 'url'],
            'settings.token' => ['nullable', 'string', 'min:1', 'max:255'],
        ];
    }
}
