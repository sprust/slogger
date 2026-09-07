<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Types;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Watcher\Domain\Services\Checkers\NoNewTracesChecker;
use App\Modules\Watcher\Domain\Services\Checkers\WatcherCheckerInterface;
use App\Modules\Watcher\Entities\Settings\NoNewTracesSettingsObject;
use App\Modules\Watcher\Entities\Settings\WatcherSettingsInterface;
use App\Modules\Watcher\Entities\WatcherTypeFieldObject;
use App\Modules\Watcher\Entities\WatcherTypeObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;

readonly class NoNewTracesWatcherType implements WatcherTypeDefinitionInterface
{
    use WatcherTraceFilterTrait;

    public function __construct(
        private NoNewTracesChecker $checker
    ) {
    }

    public function makeSettings(array $settings): WatcherSettingsInterface
    {
        return new NoNewTracesSettingsObject(
            periodMinutes: ArrayValueGetter::intNull($settings, 'period_minutes') ?? 10,
            filter: $this->filterOf($settings)
        );
    }

    public function describe(): WatcherTypeObject
    {
        return new WatcherTypeObject(
            type: WatcherTypeEnum::NoNewTraces,
            title: 'No new traces',
            description: 'Nothing matching the filter has arrived for the whole period.',
            defaultCooldownSeconds: WatcherTypeEnum::NoNewTraces->defaultCooldownSeconds(),
            hasTraceFilter: true,
            fields: [
                new WatcherTypeFieldObject(
                    key: 'period_minutes',
                    title: 'Silence, minutes',
                    valueType: 'int',
                    default: new NoNewTracesSettingsObject()->periodMinutes
                ),
            ]
        );
    }

    public function checker(): WatcherCheckerInterface
    {
        return $this->checker;
    }
}
