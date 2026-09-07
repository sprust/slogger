<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Types;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Watcher\Domain\Services\Checkers\InvalidBufferGrownChecker;
use App\Modules\Watcher\Domain\Services\Checkers\WatcherCheckerInterface;
use App\Modules\Watcher\Entities\Settings\InvalidBufferGrownSettingsObject;
use App\Modules\Watcher\Entities\Settings\WatcherSettingsInterface;
use App\Modules\Watcher\Entities\WatcherTypeFieldObject;
use App\Modules\Watcher\Entities\WatcherTypeObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;

readonly class InvalidBufferGrownWatcherType implements WatcherTypeDefinitionInterface
{
    public function __construct(
        private InvalidBufferGrownChecker $checker
    ) {
    }

    public function makeSettings(array $settings): WatcherSettingsInterface
    {
        return new InvalidBufferGrownSettingsObject(
            threshold: ArrayValueGetter::intNull($settings, 'threshold') ?? 1
        );
    }

    public function describe(): WatcherTypeObject
    {
        return new WatcherTypeObject(
            type: WatcherTypeEnum::InvalidBufferGrown,
            title: 'Invalid buffer grew',
            description: 'Documents the receiver could not act on have appeared since the last check.',
            defaultCooldownSeconds: WatcherTypeEnum::InvalidBufferGrown->defaultCooldownSeconds(),
            hasTraceFilter: false,
            fields: [
                new WatcherTypeFieldObject(
                    key: 'threshold',
                    title: 'New invalid documents',
                    valueType: 'int',
                    default: new InvalidBufferGrownSettingsObject()->threshold
                ),
            ]
        );
    }

    public function checker(): WatcherCheckerInterface
    {
        return $this->checker;
    }
}
