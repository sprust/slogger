<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Types;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Watcher\Domain\Services\Checkers\BufferOverflowChecker;
use App\Modules\Watcher\Domain\Services\Checkers\WatcherCheckerInterface;
use App\Modules\Watcher\Entities\Settings\BufferOverflowSettingsObject;
use App\Modules\Watcher\Entities\Settings\WatcherSettingsInterface;
use App\Modules\Watcher\Entities\WatcherTypeFieldObject;
use App\Modules\Watcher\Entities\WatcherTypeObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;

readonly class BufferOverflowWatcherType implements WatcherTypeDefinitionInterface
{
    public function __construct(
        private BufferOverflowChecker $checker
    ) {
    }

    public function makeSettings(array $settings): WatcherSettingsInterface
    {
        return new BufferOverflowSettingsObject(
            threshold: ArrayValueGetter::intNull($settings, 'threshold') ?? 1000
        );
    }

    public function describe(): WatcherTypeObject
    {
        return new WatcherTypeObject(
            type: WatcherTypeEnum::BufferOverflow,
            title: 'Buffer overflow',
            description: 'The intake buffer has stopped draining.',
            defaultCooldownSeconds: WatcherTypeEnum::BufferOverflow->defaultCooldownSeconds(),
            hasTraceFilter: false,
            fields: [
                new WatcherTypeFieldObject(
                    key: 'threshold',
                    title: 'Documents in the buffer',
                    valueType: 'int',
                    default: new BufferOverflowSettingsObject()->threshold
                ),
            ]
        );
    }

    public function checker(): WatcherCheckerInterface
    {
        return $this->checker;
    }
}
