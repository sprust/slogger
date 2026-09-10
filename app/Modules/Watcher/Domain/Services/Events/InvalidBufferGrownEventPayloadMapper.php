<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Events;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Watcher\Entities\Events\InvalidBufferGrownEventMeasuredObject;
use App\Modules\Watcher\Entities\Events\InvalidBufferGrownEventPayloadObject;
use App\Modules\Watcher\Entities\Events\InvalidBufferGrownEventSettingsObject;
use App\Modules\Watcher\Entities\Events\WatcherEventPayloadInterface;
use LogicException;

readonly class InvalidBufferGrownEventPayloadMapper implements WatcherEventPayloadMapperInterface
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

        $measuredInvalidCount = ArrayValueGetter::intNull($measured, 'invalid_count');
        $measuredSince        = ArrayValueGetter::stringNull($measured, 'since');

        if (is_null($settingsThreshold)
            || is_null($measuredInvalidCount)
            || is_null($measuredSince)
        ) {
            return null;
        }

        return new InvalidBufferGrownEventPayloadObject(
            settings: new InvalidBufferGrownEventSettingsObject(
                threshold: $settingsThreshold
            ),
            measured: new InvalidBufferGrownEventMeasuredObject(
                invalidCount: $measuredInvalidCount,
                since: $measuredSince
            )
        );
    }

    public function toDocument(WatcherEventPayloadInterface $payload): array
    {
        if (!$payload instanceof InvalidBufferGrownEventPayloadObject) {
            throw new LogicException('A payload of another watcher type reached this mapper');
        }

        return [
            'settings' => [
                'threshold' => $payload->settings->threshold,
            ],
            'measured' => [
                'invalid_count' => $payload->measured->invalidCount,
                'since'         => $payload->measured->since,
            ],
        ];
    }
}
