<?php

declare(strict_types=1);

namespace App\Modules\Trace\Parameters;

use App\Modules\Trace\Enums\PeriodPresetEnum;
use Illuminate\Support\Carbon;

class PeriodParameters
{
    public function __construct(
        public ?Carbon $from = null,
        public ?Carbon $to = null
    ) {
    }

    public static function fromStringValues(
        ?string $fromPreset,
        ?string $from,
        ?string $to
    ): ?static {
        if ($fromPreset) {
            $fromPreset = PeriodPresetEnum::from($fromPreset);

            $toForPreset = ($to ?: now())->clone();

            $from = match ($fromPreset) {
                PeriodPresetEnum::LastHour => $toForPreset->subHour(),
                PeriodPresetEnum::LastDay => $toForPreset->subDay(),
                PeriodPresetEnum::LastWeek => $toForPreset->subWeek(),
                PeriodPresetEnum::LastMonth => $toForPreset->subMonth(),
            };
        }

        $period = new static(
            from: $from ? (new Carbon($from))->setTimezone('UTC') : null,
            to: $to ? (new Carbon($to))->setTimezone('UTC') : null,
        );

        if (!$period->from && !$period->to) {
            return null;
        }

        return $period;
    }
}
