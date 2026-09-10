<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Types;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Watcher\Domain\Services\Checkers\SlowTracesChecker;
use App\Modules\Watcher\Domain\Services\Events\SlowTracesEventPayloadMapper;
use App\Modules\Watcher\Domain\Services\Events\WatcherEventPayloadMapperInterface;
use App\Modules\Watcher\Domain\Services\Checkers\WatcherCheckerInterface;
use App\Modules\Watcher\Entities\Settings\SlowTracesSettingsObject;
use App\Modules\Watcher\Entities\Settings\WatcherSettingsInterface;
use App\Modules\Watcher\Entities\WatcherTypeFieldObject;
use App\Modules\Watcher\Entities\WatcherTimelineObject;
use App\Modules\Watcher\Entities\WatcherTypeObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;

readonly class SlowTracesWatcherType implements WatcherTypeDefinitionInterface
{
    use WatcherTraceFilterTrait;

    public function __construct(
        private SlowTracesChecker $checker,
        private SlowTracesEventPayloadMapper $eventPayloadMapper
    ) {
    }

    public function makeSettings(array $settings): WatcherSettingsInterface
    {
        return new SlowTracesSettingsObject(
            duration: ArrayValueGetter::floatNull($settings, 'duration') ?? 10,
            windowMinutes: ArrayValueGetter::intNull($settings, 'window_minutes') ?? 5,
            filter: $this->filterOf($settings)
        );
    }

    public function describe(): WatcherTypeObject
    {
        return new WatcherTypeObject(
            type: WatcherTypeEnum::SlowTraces,
            title: 'Slow traces',
            description: 'A trace matching the filter took longer than the limit.',
            defaultCooldownSeconds: WatcherTypeEnum::SlowTraces->defaultCooldownSeconds(),
            hasTraceFilter: true,
            fields: [
                new WatcherTypeFieldObject(
                    key: 'duration',
                    title: 'Longer than, seconds',
                    valueType: 'float',
                    default: new SlowTracesSettingsObject()->duration,
                    min: 0
                ),
                new WatcherTypeFieldObject(
                    key: 'window_minutes',
                    title: 'Look at the last, minutes',
                    valueType: 'int',
                    default: new SlowTracesSettingsObject()->windowMinutes,
                    max: WatcherTimelineObject::MAX_DEPTH_MINUTES
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
