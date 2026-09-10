<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Events;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Watcher\Entities\Events\SlowTracesEventMeasuredObject;
use App\Modules\Watcher\Entities\Events\SlowTracesEventPayloadObject;
use App\Modules\Watcher\Entities\Events\SlowTracesEventSettingsObject;
use App\Modules\Watcher\Entities\Events\WatcherEventPayloadInterface;
use LogicException;

readonly class SlowTracesEventPayloadMapper implements WatcherEventPayloadMapperInterface
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
        $settingsDuration      = ArrayValueGetter::floatNull($settings, 'duration');
        $settingsWindowMinutes = ArrayValueGetter::intNull($settings, 'window_minutes');

        $measuredSlowest = ArrayValueGetter::floatNull($measured, 'slowest');

        if (is_null($settingsDuration)
            || is_null($settingsWindowMinutes)
            || is_null($measuredSlowest)
        ) {
            return null;
        }

        return new SlowTracesEventPayloadObject(
            settings: new SlowTracesEventSettingsObject(
                duration: $settingsDuration,
                windowMinutes: $settingsWindowMinutes
            ),
            measured: new SlowTracesEventMeasuredObject(
                slowest: $measuredSlowest
            ),
            groups: $this->groups->read($payload['groups'] ?? null)
        );
    }

    public function toDocument(WatcherEventPayloadInterface $payload): array
    {
        if (!$payload instanceof SlowTracesEventPayloadObject) {
            throw new LogicException('A payload of another watcher type reached this mapper');
        }

        return [
            'settings' => [
                'duration'       => $payload->settings->duration,
                'window_minutes' => $payload->settings->windowMinutes,
            ],
            'measured' => [
                'slowest' => $payload->measured->slowest,
            ],
            'groups'   => $this->groups->toDocument($payload->groups),
        ];
    }
}
