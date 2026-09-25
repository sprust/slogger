<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Types;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Watcher\Domain\Services\Checkers\ReceiverErrorsChecker;
use App\Modules\Watcher\Domain\Services\Checkers\WatcherCheckerInterface;
use App\Modules\Watcher\Domain\Services\Events\LogErrorsEventPayloadMapper;
use App\Modules\Watcher\Domain\Services\Events\WatcherEventPayloadMapperInterface;
use App\Modules\Watcher\Entities\Settings\LogErrorsSettingsObject;
use App\Modules\Watcher\Entities\Settings\WatcherSettingsInterface;
use App\Modules\Watcher\Entities\WatcherTypeFieldObject;
use App\Modules\Watcher\Entities\WatcherTypeObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;

/**
 * Same settings and event as logErrors, counted over the receiver logs.
 */
readonly class ReceiverErrorsWatcherType implements WatcherTypeDefinitionInterface
{
    public function __construct(
        private ReceiverErrorsChecker $checker,
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
            type: WatcherTypeEnum::ReceiverErrors,
            title: 'Errors in receiver logs',
            description: 'Errors have been written to the receiver log since the last check.',
            defaultCooldownSeconds: WatcherTypeEnum::ReceiverErrors->defaultCooldownSeconds(),
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
