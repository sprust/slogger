<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Services;

use App\Modules\Notification\Domain\Services\Senders\NotificationSenderInterface;
use App\Modules\Notification\Enums\NotificationKindEnum;
use App\Modules\Watcher\Domain\Services\Types\WatcherTypeRegistry;
use App\Modules\Watcher\Entities\WatcherIncidentEventObject;
use App\Modules\Watcher\Entities\WatcherIncidentObject;
use App\Modules\Watcher\Entities\WatcherObject;

readonly class IncidentMessageFactory
{
    private const int MAX_GROUPS = 3;

    /** @var string[] */
    private const array SECOND_KEYS = ['duration', 'slowest', 'duration_max'];

    public function __construct(
        private WatcherTypeRegistry $watcherTypes,
        private string $appName
    ) {
    }

    public function make(
        NotificationKindEnum $kind,
        WatcherObject $watcher,
        WatcherIncidentObject $incident,
        ?WatcherIncidentEventObject $event,
        NotificationSenderInterface $sender
    ): string {
        $blocks = [
            $this->title($kind, $sender),
            implode("\n", $this->head($kind, $watcher, $incident, $sender)),
        ];

        if ($kind === NotificationKindEnum::Closed) {
            return implode("\n\n", $blocks);
        }

        $payload = is_null($event) ? [] : $event->payload;

        $details = $this->details($payload, $sender);

        if (count($details)) {
            $blocks[] = "📊 details:\n" . implode("\n", $details);
        }

        $traces = $this->traces($payload['groups'] ?? null, $sender);

        if (count($traces)) {
            $blocks[] = "🔎 traces:\n" . implode("\n", $traces);
        }

        return implode("\n\n", $blocks);
    }

    private function title(NotificationKindEnum $kind, NotificationSenderInterface $sender): string
    {
        $mark = match ($kind) {
            NotificationKindEnum::Opened => '🔴',
            NotificationKindEnum::Event  => '🟠',
            NotificationKindEnum::Closed => '🟢',
        };

        return $mark . ' ' . $sender->bold(
            $sender->escape($this->appName) . ' (' . $kind->value . ')'
        );
    }

    /**
     * @return string[]
     */
    private function head(
        NotificationKindEnum $kind,
        WatcherObject $watcher,
        WatcherIncidentObject $incident,
        NotificationSenderInterface $sender
    ): array {
        $lines = [
            '🏷 name: ' . $sender->escape($watcher->name),
            '📛 type: ' . $sender->escape($this->watcherTypes->for($watcher->type)->describe()->title),
        ];

        if ($kind === NotificationKindEnum::Closed) {
            $lines[] = '✅ closedAt: ' . ($incident->closedAt ?? $incident->lastEventAt)->toDateTimeString();
            $lines[] = '🕒 firstEvent: ' . $incident->firstEventAt->toDateTimeString();
            $lines[] = '🔢 events: ' . $incident->eventsCount;

            return $lines;
        }

        $lines[] = '🕒 lastEvent: ' . $incident->lastEventAt->toDateTimeString();

        if ($incident->eventsCount > 1) {
            $lines[] = '🔁 event: ' . $incident->eventsCount . ' of this incident';
        }

        return $lines;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return string[]
     */
    private function details(array $payload, NotificationSenderInterface $sender): array
    {
        $lines = [];

        foreach ($payload as $key => $value) {
            if ($key === 'groups' || !is_scalar($value)) {
                continue;
            }

            $lines[] = sprintf(
                '• %s: %s',
                str_replace('_', ' ', $key),
                $sender->escape($this->readable($key, $value))
            );
        }

        return $lines;
    }

    /**
     * @return string[]
     */
    private function traces(mixed $groups, NotificationSenderInterface $sender): array
    {
        if (!is_array($groups) || !count($groups)) {
            return [];
        }

        $lines = [];

        foreach (array_slice($groups, 0, self::MAX_GROUPS) as $group) {
            if (!is_array($group)) {
                continue;
            }

            $named = array_filter(
                [
                    $group['type'] ?? null,
                    ...(is_array($group['tags'] ?? null) ? $group['tags'] : []),
                ],
                is_string(...)
            );

            $lines[] = '• ' . $sender->escape(implode(' ', $named));

            $count = $group['count'] ?? null;
            $max   = $group['duration_max'] ?? null;

            if (is_scalar($count) && is_scalar($max)) {
                $lines[] = sprintf(
                    '  %d trace%s, up to %s',
                    (int) $count,
                    (int) $count === 1 ? '' : 's',
                    $sender->escape($this->readable('duration_max', $max))
                );
            }

            if (is_scalar($group['trace_id'] ?? null)) {
                $lines[] = '  ' . $sender->code($sender->escape($this->readable('trace_id', $group['trace_id'])));
            }
        }

        return $lines;
    }

    private function readable(string $key, mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        $value = (string) $value;

        return in_array($key, self::SECOND_KEYS, true) ? $value . 's' : $value;
    }
}
