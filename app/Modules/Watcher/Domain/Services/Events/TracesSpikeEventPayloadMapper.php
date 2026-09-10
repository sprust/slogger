<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Events;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Watcher\Entities\Events\TracesSpikeEventMeasuredObject;
use App\Modules\Watcher\Entities\Events\TracesSpikeEventPayloadObject;
use App\Modules\Watcher\Entities\Events\TracesSpikeEventSettingsObject;
use App\Modules\Watcher\Entities\Events\WatcherEventPayloadInterface;
use LogicException;

readonly class TracesSpikeEventPayloadMapper implements WatcherEventPayloadMapperInterface
{
    public function __construct(
        private WatcherEventGroupMapper $groups
    ) {
    }

    public function read(array $payload): ?WatcherEventPayloadInterface
    {
        $settings = $payload['settings'] ?? null;
        $measured = $payload['measured'] ?? null;

        if (!is_array($settings) || !is_array($measured)) {
            return null;
        }

        /** @var array<string, mixed> $settings */
        /** @var array<string, mixed> $measured */
        $settingsWindowMinutes   = ArrayValueGetter::intNull($settings, 'window_minutes');
        $settingsBaselineMinutes = ArrayValueGetter::intNull($settings, 'baseline_minutes');
        $settingsGrowthPercent   = ArrayValueGetter::intNull($settings, 'growth_percent');

        $measuredWindowCount       = ArrayValueGetter::intNull($measured, 'window_count');
        $measuredWindowPerMinute   = ArrayValueGetter::floatNull($measured, 'window_per_minute');
        $measuredBaselinePerMinute = ArrayValueGetter::floatNull($measured, 'baseline_per_minute');
        $measuredGrowthPercent     = ArrayValueGetter::floatNull($measured, 'growth_percent');

        if (is_null($settingsWindowMinutes)
            || is_null($settingsBaselineMinutes)
            || is_null($settingsGrowthPercent)
            || is_null($measuredWindowCount)
            || is_null($measuredWindowPerMinute)
            || is_null($measuredBaselinePerMinute)
            || is_null($measuredGrowthPercent)
        ) {
            return null;
        }

        return new TracesSpikeEventPayloadObject(
            settings: new TracesSpikeEventSettingsObject(
                windowMinutes: $settingsWindowMinutes,
                baselineMinutes: $settingsBaselineMinutes,
                growthPercent: $settingsGrowthPercent
            ),
            measured: new TracesSpikeEventMeasuredObject(
                windowCount: $measuredWindowCount,
                windowPerMinute: $measuredWindowPerMinute,
                baselinePerMinute: $measuredBaselinePerMinute,
                growthPercent: $measuredGrowthPercent
            ),
            groups: $this->groups->read($payload['groups'] ?? null)
        );
    }

    public function toDocument(WatcherEventPayloadInterface $payload): array
    {
        if (!$payload instanceof TracesSpikeEventPayloadObject) {
            throw new LogicException('A payload of another watcher type reached this mapper');
        }

        return [
            'settings' => [
                'window_minutes'   => $payload->settings->windowMinutes,
                'baseline_minutes' => $payload->settings->baselineMinutes,
                'growth_percent'   => $payload->settings->growthPercent,
            ],
            'measured' => [
                'window_count'        => $payload->measured->windowCount,
                'window_per_minute'   => $payload->measured->windowPerMinute,
                'baseline_per_minute' => $payload->measured->baselinePerMinute,
                'growth_percent'      => $payload->measured->growthPercent,
            ],
            'groups'   => $this->groups->toDocument($payload->groups),
        ];
    }
}
