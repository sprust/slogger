<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Events;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Watcher\Entities\Events\LogErrorsEventMeasuredObject;
use App\Modules\Watcher\Entities\Events\LogErrorsEventPayloadObject;
use App\Modules\Watcher\Entities\Events\LogErrorsEventSettingsObject;
use App\Modules\Watcher\Entities\Events\WatcherEventPayloadInterface;
use LogicException;

readonly class LogErrorsEventPayloadMapper implements WatcherEventPayloadMapperInterface
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

        $measuredErrorCount  = ArrayValueGetter::intNull($measured, 'error_count');
        $measuredSince       = ArrayValueGetter::stringNull($measured, 'since');
        $measuredLastMessage = ArrayValueGetter::stringNull($measured, 'last_message');

        if (is_null($settingsThreshold)
            || is_null($measuredErrorCount)
            || is_null($measuredSince)
            || is_null($measuredLastMessage)
        ) {
            return null;
        }

        return new LogErrorsEventPayloadObject(
            settings: new LogErrorsEventSettingsObject(
                threshold: $settingsThreshold
            ),
            measured: new LogErrorsEventMeasuredObject(
                errorCount: $measuredErrorCount,
                since: $measuredSince,
                lastMessage: $measuredLastMessage
            )
        );
    }

    public function toDocument(WatcherEventPayloadInterface $payload): array
    {
        if (!$payload instanceof LogErrorsEventPayloadObject) {
            throw new LogicException('A payload of another watcher type reached this mapper');
        }

        return [
            'settings' => [
                'threshold' => $payload->settings->threshold,
            ],
            'measured' => [
                'error_count'  => $payload->measured->errorCount,
                'since'        => $payload->measured->since,
                'last_message' => $payload->measured->lastMessage,
            ],
        ];
    }
}
