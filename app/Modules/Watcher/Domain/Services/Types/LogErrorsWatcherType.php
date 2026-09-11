<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Types;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Watcher\Domain\Services\Checkers\LogErrorsChecker;
use App\Modules\Watcher\Domain\Services\Checkers\WatcherCheckerInterface;
use App\Modules\Watcher\Domain\Services\Events\LogErrorsEventPayloadMapper;
use App\Modules\Watcher\Domain\Services\Events\WatcherEventPayloadMapperInterface;
use App\Modules\Watcher\Entities\Settings\LogErrorsSettingsObject;
use App\Modules\Watcher\Entities\Settings\WatcherSettingsInterface;
use App\Modules\Watcher\Entities\WatcherTypeFieldObject;
use App\Modules\Watcher\Entities\WatcherTypeObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;

readonly class LogErrorsWatcherType implements WatcherTypeDefinitionInterface
{
    public function __construct(
        private LogErrorsChecker $checker,
        private LogErrorsEventPayloadMapper $eventPayloadMapper
    ) {
    }

    public function makeSettings(array $settings): WatcherSettingsInterface
    {
        return new LogErrorsSettingsObject(
            threshold: ArrayValueGetter::intNull($settings, 'threshold') ?? 1
        );
    }

    public function describe(): WatcherTypeObject
    {
        return new WatcherTypeObject(
            type: WatcherTypeEnum::LogErrors,
            title: 'Errors in logs',
            description: 'Errors have been written to the application log since the last check.',
            defaultCooldownSeconds: WatcherTypeEnum::LogErrors->defaultCooldownSeconds(),
            hasTraceFilter: false,
            fields: [
                new WatcherTypeFieldObject(
                    key: 'threshold',
                    title: 'New errors',
                    valueType: 'int',
                    default: new LogErrorsSettingsObject()->threshold
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
