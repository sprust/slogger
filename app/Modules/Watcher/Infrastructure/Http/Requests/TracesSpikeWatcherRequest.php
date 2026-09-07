<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TracesSpikeWatcherRequest extends FormRequest
{
    use WatcherRulesTrait;

    public function rules(): array
    {
        return [
            ...$this->commonRules(),
            'settings'                  => ['required', 'array'],
            'settings.window_minutes'   => ['required', 'integer', 'min:1', 'max:1440'],
            'settings.baseline_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'settings.growth_percent'   => ['required', 'integer', 'min:1'],
            ...$this->traceFilterRules(),
        ];
    }
}
