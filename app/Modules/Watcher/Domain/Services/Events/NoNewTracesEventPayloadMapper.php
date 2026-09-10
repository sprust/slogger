<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Events;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Watcher\Entities\Events\NoNewTracesEventMeasuredObject;
use App\Modules\Watcher\Entities\Events\NoNewTracesEventPayloadObject;
use App\Modules\Watcher\Entities\Events\NoNewTracesEventSettingsObject;
use App\Modules\Watcher\Entities\Events\WatcherEventPayloadInterface;
use LogicException;

readonly class NoNewTracesEventPayloadMapper implements WatcherEventPayloadMapperInterface
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
        $settingsPeriodMinutes = ArrayValueGetter::intNull($settings, 'period_minutes');

        $measuredWindowFrom = ArrayValueGetter::stringNull($measured, 'window_from');
        $measuredWindowTo   = ArrayValueGetter::stringNull($measured, 'window_to');

        if (is_null($settingsPeriodMinutes)
            || is_null($measuredWindowFrom)
            || is_null($measuredWindowTo)
        ) {
            return null;
        }

        return new NoNewTracesEventPayloadObject(
            settings: new NoNewTracesEventSettingsObject(
                periodMinutes: $settingsPeriodMinutes
            ),
            measured: new NoNewTracesEventMeasuredObject(
                windowFrom: $measuredWindowFrom,
                windowTo: $measuredWindowTo
            )
        );
    }

    public function toDocument(WatcherEventPayloadInterface $payload): array
    {
        if (!$payload instanceof NoNewTracesEventPayloadObject) {
            throw new LogicException('A payload of another watcher type reached this mapper');
        }

        return [
            'settings' => [
                'period_minutes' => $payload->settings->periodMinutes,
            ],
            'measured' => [
                'window_from' => $payload->measured->windowFrom,
                'window_to'   => $payload->measured->windowTo,
            ],
        ];
    }
}
