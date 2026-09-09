<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SlowTracesWatcherRequest extends FormRequest
{
    use WatcherRulesTrait;

    public function rules(): array
    {
        return [
            ...$this->commonRules(),
            'settings'                => ['required', 'array'],
            // Above zero, not from it: every duration is at least zero, so a watcher set
            // to zero reports every trace it is about, every cooldown, for ever.
            'settings.duration'       => ['required', 'numeric', 'min:0.001'],
            'settings.window_minutes' => $this->timelineMinutesRules(),
            ...$this->traceFilterRules(),
        ];
    }
}
