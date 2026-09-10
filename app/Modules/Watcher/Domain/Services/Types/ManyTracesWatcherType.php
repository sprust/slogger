<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Types;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Watcher\Domain\Services\Checkers\ManyTracesChecker;
use App\Modules\Watcher\Domain\Services\Events\ManyTracesEventPayloadMapper;
use App\Modules\Watcher\Domain\Services\Events\WatcherEventPayloadMapperInterface;
use App\Modules\Watcher\Domain\Services\Checkers\WatcherCheckerInterface;
use App\Modules\Watcher\Entities\Settings\ManyTracesSettingsObject;
use App\Modules\Watcher\Entities\Settings\WatcherSettingsInterface;
use App\Modules\Watcher\Entities\WatcherTypeFieldObject;
use App\Modules\Watcher\Entities\WatcherTimelineObject;
use App\Modules\Watcher\Entities\WatcherTypeObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;

readonly class ManyTracesWatcherType implements WatcherTypeDefinitionInterface
{
    use WatcherTraceFilterTrait;

    public function __construct(
        private ManyTracesChecker $checker,
        private ManyTracesEventPayloadMapper $eventPayloadMapper
    ) {
    }

    public function makeSettings(array $settings): WatcherSettingsInterface
    {
        return new ManyTracesSettingsObject(
            windowMinutes: ArrayValueGetter::intNull($settings, 'window_minutes') ?? 5,
            threshold: ArrayValueGetter::intNull($settings, 'threshold') ?? 1000,
            filter: $this->filterOf($settings)
        );
    }

    public function describe(): WatcherTypeObject
    {
        return new WatcherTypeObject(
            type: WatcherTypeEnum::ManyTraces,
            title: 'Too many traces',
            description: 'More traces than the limit arrived in the last few minutes.',
            defaultCooldownSeconds: WatcherTypeEnum::ManyTraces->defaultCooldownSeconds(),
            hasTraceFilter: true,
            fields: [
                new WatcherTypeFieldObject(
                    key: 'window_minutes',
                    title: 'Over the last, min',
                    valueType: 'int',
                    default: new ManyTracesSettingsObject()->windowMinutes,
                    max: WatcherTimelineObject::MAX_DEPTH_MINUTES
                ),
                new WatcherTypeFieldObject(
                    key: 'threshold',
                    title: 'More traces than',
                    valueType: 'int',
                    default: new ManyTracesSettingsObject()->threshold,
                    min: 1
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
