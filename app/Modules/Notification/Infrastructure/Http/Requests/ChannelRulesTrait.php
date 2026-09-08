<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Http\Requests;

trait ChannelRulesTrait
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function commonRules(): array
    {
        return [
            'name'      => ['required', 'string', 'min:1', 'max:255'],
            'enabled'   => ['required', 'boolean'],
            'on_opened' => ['required', 'boolean'],
            'on_event'  => ['required', 'boolean'],
            'on_closed' => ['required', 'boolean'],
        ];
    }
}
