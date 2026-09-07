<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Repositories\Services;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Watcher\Entities\Settings\BufferOverflowSettingsObject;
use App\Modules\Watcher\Entities\Settings\HasTraceFilterInterface;
use App\Modules\Watcher\Entities\Settings\InvalidBufferGrownSettingsObject;
use App\Modules\Watcher\Entities\Settings\NoNewTracesSettingsObject;
use App\Modules\Watcher\Entities\Settings\SlowTracesSettingsObject;
use App\Modules\Watcher\Entities\Settings\TracesSpikeSettingsObject;
use App\Modules\Watcher\Entities\Settings\WatcherSettingsInterface;
use App\Modules\Watcher\Entities\Settings\WatcherTraceFilterObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use RuntimeException;

/**
 * The stored json of a watcher's settings, both ways.
 *
 * Reading fills in defaults for whatever is missing rather than failing: a settings
 * column written before a field existed is the ordinary case after a release, and a
 * watcher that will not load is worse than one running on the default.
 */
readonly class WatcherSettingsMapper
{
    /**
     * @param array<string, mixed> $raw
     */
    public function toObject(WatcherTypeEnum $type, array $raw): WatcherSettingsInterface
    {
        return match ($type) {
            WatcherTypeEnum::BufferOverflow => new BufferOverflowSettingsObject(
                threshold: ArrayValueGetter::intNull($raw, 'threshold') ?? 1000
            ),
            WatcherTypeEnum::InvalidBufferGrown => new InvalidBufferGrownSettingsObject(
                threshold: ArrayValueGetter::intNull($raw, 'threshold') ?? 1
            ),
            WatcherTypeEnum::NoNewTraces => new NoNewTracesSettingsObject(
                periodMinutes: ArrayValueGetter::intNull($raw, 'period_minutes') ?? 10,
                filter: $this->filterToObject($raw)
            ),
            WatcherTypeEnum::TracesSpike => new TracesSpikeSettingsObject(
                windowMinutes: ArrayValueGetter::intNull($raw, 'window_minutes') ?? 5,
                baselineMinutes: ArrayValueGetter::intNull($raw, 'baseline_minutes') ?? 60,
                growthPercent: ArrayValueGetter::intNull($raw, 'growth_percent') ?? 90,
                filter: $this->filterToObject($raw)
            ),
            WatcherTypeEnum::SlowTraces => new SlowTracesSettingsObject(
                duration: ArrayValueGetter::floatNull($raw, 'duration') ?? 10,
                windowMinutes: ArrayValueGetter::intNull($raw, 'window_minutes') ?? 5,
                filter: $this->filterToObject($raw)
            ),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(WatcherSettingsInterface $settings): array
    {
        $values = match (true) {
            $settings instanceof BufferOverflowSettingsObject => [
                'threshold' => $settings->threshold,
            ],
            $settings instanceof InvalidBufferGrownSettingsObject => [
                'threshold' => $settings->threshold,
            ],
            $settings instanceof NoNewTracesSettingsObject => [
                'period_minutes' => $settings->periodMinutes,
            ],
            $settings instanceof TracesSpikeSettingsObject => [
                'window_minutes'   => $settings->windowMinutes,
                'baseline_minutes' => $settings->baselineMinutes,
                'growth_percent'   => $settings->growthPercent,
            ],
            $settings instanceof SlowTracesSettingsObject => [
                'duration'       => $settings->duration,
                'window_minutes' => $settings->windowMinutes,
            ],
            default => throw new RuntimeException(
                'Unknown watcher settings: ' . $settings::class
            ),
        };

        if ($settings instanceof HasTraceFilterInterface) {
            $filter = $settings->traceFilter();

            $values['filter'] = [
                'service_ids' => $filter->serviceIds,
                'types'       => $filter->types,
                'tags'        => $filter->tags,
            ];
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $raw
     */
    private function filterToObject(array $raw): WatcherTraceFilterObject
    {
        $filter = $raw['filter'] ?? null;

        if (!is_array($filter)) {
            return new WatcherTraceFilterObject();
        }

        /** @var array<string, mixed> $filter */
        return new WatcherTraceFilterObject(
            serviceIds: array_values(ArrayValueGetter::arrayIntNull($filter, 'service_ids') ?? []),
            types: array_values(ArrayValueGetter::arrayStringNull($filter, 'types') ?? []),
            tags: array_values(ArrayValueGetter::arrayStringNull($filter, 'tags') ?? [])
        );
    }
}
