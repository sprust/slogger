<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Requests;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Watcher\Entities\WatcherTimelineObject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TracesSpikeWatcherRequest extends FormRequest
{
    use WatcherRulesTrait;

    public function rules(): array
    {
        return [
            ...$this->commonRules(),
            'settings'                  => ['required', 'array'],
            'settings.window_minutes'   => $this->timelineMinutesRules(),
            'settings.baseline_minutes' => $this->timelineMinutesRules(),
            'settings.growth_percent'   => ['required', 'integer', 'min:1'],
            ...$this->traceFilterRules(),
        ];
    }

    /**
     * The two stretches are read one after the other, so together they have to fit in what
     * a line holds. Each on its own is within the cap and the pair still need not be: an
     * hour of window against three hours of baseline reaches back four.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $settings = $this->input('settings');

            if (!is_array($settings)) {
                return;
            }

            /** @var array<string, mixed> $settings */
            $window   = ArrayValueGetter::intNull($settings, 'window_minutes');
            $baseline = ArrayValueGetter::intNull($settings, 'baseline_minutes');

            if (is_null($window) || is_null($baseline)) {
                return;
            }

            $depth = WatcherTimelineObject::MAX_DEPTH_MINUTES;

            if ($window + $baseline > $depth) {
                $validator->errors()->add(
                    'settings.baseline_minutes',
                    "The window and the baseline together may not go back further than $depth minutes."
                );
            }
        });
    }
}
