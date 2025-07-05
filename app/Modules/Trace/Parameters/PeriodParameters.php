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
        $from = $from ? (new Carbon($from))->setTimezone('UTC') : null;
        $to   = $to ? (new Carbon($to))->setTimezone('UTC') : null;

        if ($fromPreset) {
            $fromPreset = PeriodPresetEnum::from($fromPreset);

            $toForPreset = $to?->clone() ?: now();

            $from = match ($fromPreset) {
                PeriodPresetEnum::Custom => $from,
                PeriodPresetEnum::LastHour => $toForPreset->subHour(),
                PeriodPresetEnum::Last2Hours => $toForPreset->subHours(2),
                PeriodPresetEnum::Last3Hours => $toForPreset->subHours(3),
                PeriodPresetEnum::Last6Hours => $toForPreset->subHours(6),
                PeriodPresetEnum::Last12Hours => $toForPreset->subHours(12),
                PeriodPresetEnum::LastDay => $toForPreset->subDay(),
                PeriodPresetEnum::Last3Days => $toForPreset->subDays(),
                PeriodPresetEnum::LastWeek => $toForPreset->subWeek(),
                PeriodPresetEnum::Last2Weeks => $toForPreset->subWeeks(2),
                PeriodPresetEnum::LastMonth => $toForPreset->subMonth(),
            };
        }

        $period = new static(
            from: $from,
            to: $to,
        );

        if (!$period->from && !$period->to) {
            return null;
        }

        return $period;
    }
}
