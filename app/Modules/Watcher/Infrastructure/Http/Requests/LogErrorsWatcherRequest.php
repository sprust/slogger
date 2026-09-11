<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LogErrorsWatcherRequest extends FormRequest
{
    use WatcherRulesTrait;

    public function rules(): array
    {
        return [
            ...$this->commonRules(),
            'settings'           => ['required', 'array'],
            'settings.threshold' => ['required', 'integer', 'min:1'],
        ];
    }
}
