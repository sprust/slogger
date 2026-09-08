<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The token is optional here: the form was shown a mask, so an empty one means the stored
 * token stays.
 */
class UpdateTelegramChannelRequest extends FormRequest
{
    use ChannelRulesTrait;

    public function rules(): array
    {
        return [
            ...$this->commonRules(),
            'settings'           => ['required', 'array'],
            'settings.bot_token' => ['nullable', 'string', 'min:10', 'max:255'],
            'settings.chat_id'   => ['required', 'string', 'min:5', 'max:255'],
        ];
    }
}
