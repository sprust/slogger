<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Types;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Watcher\Domain\Services\Checkers\TracesSpikeChecker;
use App\Modules\Watcher\Domain\Services\Events\TracesSpikeEventPayloadMapper;
use App\Modules\Watcher\Domain\Services\Events\WatcherEventPayloadMapperInterface;
use App\Modules\Watcher\Domain\Services\Checkers\WatcherCheckerInterface;
use App\Modules\Watcher\Entities\Settings\TracesSpikeSettingsObject;
use App\Modules\Watcher\Entities\Settings\WatcherSettingsInterface;
use App\Modules\Watcher\Entities\WatcherTypeFieldObject;
use App\Modules\Watcher\Entities\WatcherTimelineObject;
use App\Modules\Watcher\Entities\WatcherTypeObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;

readonly class TracesSpikeWatcherType implements WatcherTypeDefinitionInterface
{
    use WatcherTraceFilterTrait;

    public function __construct(
        private TracesSpikeChecker $checker,
        private TracesSpikeEventPayloadMapper $eventPayloadMapper
    ) {
    }

    public function makeSettings(array $settings): WatcherSettingsInterface
    {
        return new TracesSpikeSettingsObject(
            windowMinutes: ArrayValueGetter::intNull($settings, 'window_minutes') ?? 5,
            baselineMinutes: ArrayValueGetter::intNull($settings, 'baseline_minutes') ?? 60,
            growthPercent: ArrayValueGetter::intNull($settings, 'growth_percent') ?? 90,
            filter: $this->filterOf($settings)
        );
    }

    public function describe(): WatcherTypeObject
    {
        return new WatcherTypeObject(
            type: WatcherTypeEnum::TracesSpike,
            title: 'Traces spike',
            description: 'Many more traces per minute are arriving now than in the period before.',
            defaultCooldownSeconds: WatcherTypeEnum::TracesSpike->defaultCooldownSeconds(),
            hasTraceFilter: true,
            fields: [
                new WatcherTypeFieldObject(
                    key: 'window_minutes',
                    title: 'Count over the last, minutes',
                    valueType: 'int',
                    default: new TracesSpikeSettingsObject()->windowMinutes,
                    max: WatcherTimelineObject::MAX_DEPTH_MINUTES
                ),
                new WatcherTypeFieldObject(
                    key: 'baseline_minutes',
                    title: 'Compare with the last, minutes',
                    valueType: 'int',
                    default: new TracesSpikeSettingsObject()->baselineMinutes,
                    max: WatcherTimelineObject::MAX_DEPTH_MINUTES
                ),
                new WatcherTypeFieldObject(
                    key: 'growth_percent',
                    title: 'Growth to react to, %',
                    valueType: 'int',
                    default: new TracesSpikeSettingsObject()->growthPercent
                ),
            ]
        );
    }

    public function eventPayloadMapper(): WatcherEventPayloadMapperInterface
    {
        return $this->eventPayloadMapper;
    }

    public function checker(): WatcherCheckerInterface
    {
        return $this->checker;
    }
}
