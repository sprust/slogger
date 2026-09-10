<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Events;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Watcher\Entities\Events\ManyTracesEventMeasuredObject;
use App\Modules\Watcher\Entities\Events\ManyTracesEventPayloadObject;
use App\Modules\Watcher\Entities\Events\ManyTracesEventSettingsObject;
use App\Modules\Watcher\Entities\Events\WatcherEventPayloadInterface;
use LogicException;

readonly class ManyTracesEventPayloadMapper implements WatcherEventPayloadMapperInterface
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
        $settingsWindowMinutes = ArrayValueGetter::intNull($settings, 'window_minutes');
        $settingsThreshold     = ArrayValueGetter::intNull($settings, 'threshold');

        $measuredWindowCount = ArrayValueGetter::intNull($measured, 'window_count');

        if (is_null($settingsWindowMinutes)
            || is_null($settingsThreshold)
            || is_null($measuredWindowCount)
        ) {
            return null;
        }

        return new ManyTracesEventPayloadObject(
            settings: new ManyTracesEventSettingsObject(
                windowMinutes: $settingsWindowMinutes,
                threshold: $settingsThreshold
            ),
            measured: new ManyTracesEventMeasuredObject(
                windowCount: $measuredWindowCount
            ),
            groups: $this->groups->read($payload['groups'] ?? null)
        );
    }

    public function toDocument(WatcherEventPayloadInterface $payload): array
    {
        if (!$payload instanceof ManyTracesEventPayloadObject) {
            throw new LogicException('A payload of another watcher type reached this mapper');
        }

        return [
            'settings' => [
                'window_minutes' => $payload->settings->windowMinutes,
                'threshold'      => $payload->settings->threshold,
            ],
            'measured' => [
                'window_count' => $payload->measured->windowCount,
            ],
            'groups'   => $this->groups->toDocument($payload->groups),
        ];
    }
}
