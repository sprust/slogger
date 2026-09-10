<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Events;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Watcher\Entities\Events\BufferOverflowEventMeasuredObject;
use App\Modules\Watcher\Entities\Events\BufferOverflowEventPayloadObject;
use App\Modules\Watcher\Entities\Events\BufferOverflowEventSettingsObject;
use App\Modules\Watcher\Entities\Events\WatcherEventPayloadInterface;
use LogicException;

readonly class BufferOverflowEventPayloadMapper implements WatcherEventPayloadMapperInterface
{
    public function read(array $payload): ?WatcherEventPayloadInterface
    {
        $settings = $payload['settings'] ?? null;
        $measured = $payload['measured'] ?? null;

        if (!is_array($settings) || !is_array($measured)) {
            return null;
        }

        /** @var array<string, mixed> $settings */
        /** @var array<string, mixed> $measured */
        $settingsThreshold = ArrayValueGetter::intNull($settings, 'threshold');

        $measuredBufferCount = ArrayValueGetter::intNull($measured, 'buffer_count');

        if (is_null($settingsThreshold)
            || is_null($measuredBufferCount)
        ) {
            return null;
        }

        return new BufferOverflowEventPayloadObject(
            settings: new BufferOverflowEventSettingsObject(
                threshold: $settingsThreshold
            ),
            measured: new BufferOverflowEventMeasuredObject(
                bufferCount: $measuredBufferCount
            )
        );
    }

    public function toDocument(WatcherEventPayloadInterface $payload): array
    {
        if (!$payload instanceof BufferOverflowEventPayloadObject) {
            throw new LogicException('A payload of another watcher type reached this mapper');
        }

        return [
            'settings' => [
                'threshold' => $payload->settings->threshold,
            ],
            'measured' => [
                'buffer_count' => $payload->measured->bufferCount,
            ],
        ];
    }
}
