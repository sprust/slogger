<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManyTracesWatcherRequest extends FormRequest
{
    use WatcherRulesTrait;

    public function rules(): array
    {
        return [
            ...$this->commonRules(),
            'settings'                => ['required', 'array'],
            'settings.window_minutes' => $this->timelineMinutesRules(),
            'settings.threshold'      => ['required', 'integer', 'min:1'],
            ...$this->traceFilterRules(),
        ];
    }
}
