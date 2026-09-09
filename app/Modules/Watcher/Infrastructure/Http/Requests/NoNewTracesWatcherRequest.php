<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NoNewTracesWatcherRequest extends FormRequest
{
    use WatcherRulesTrait;

    public function rules(): array
    {
        return [
            ...$this->commonRules(),
            'settings'                => ['required', 'array'],
            'settings.period_minutes' => $this->timelineMinutesRules(),
            ...$this->traceFilterRules(),
        ];
    }
}
