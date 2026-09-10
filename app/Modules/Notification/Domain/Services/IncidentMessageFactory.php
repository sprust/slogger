<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Services;

use App\Modules\Notification\Domain\Services\Senders\NotificationSenderInterface;
use App\Modules\Notification\Enums\NotificationKindEnum;
use App\Modules\Watcher\Domain\Services\Types\WatcherTypeRegistry;
use App\Modules\Watcher\Entities\Events\HasEventGroupsInterface;
use App\Modules\Watcher\Entities\WatcherIncidentEventGroupObject;
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

        // A payload that could not be read leaves the message at its head: the event
        // happened, and saying so with no numbers beats inventing some.
        if ($kind === NotificationKindEnum::Closed || is_null($event) || is_null($event->payload)) {
            return implode("\n\n", $blocks);
        }

        // The stored document rather than the object: its keys are the names a reader of
        // the message sees, and the panel shows the same event under the same ones. The
        // mapper is the one place that holds them.
        $document = $this->watcherTypes->for($watcher->type)
            ->eventPayloadMapper()
            ->toDocument($event->payload);

        $settings = $this->values($document['settings'] ?? null, $sender);

        if (count($settings)) {
            $blocks[] = "⚙️ settings:\n" . implode("\n", $settings);
        }

        $measured = $this->values($document['measured'] ?? null, $sender);

        if (count($measured)) {
            $blocks[] = "📊 measured:\n" . implode("\n", $measured);
        }

        $traces = $this->traces(
            $event->payload instanceof HasEventGroupsInterface ? $event->payload->groups : [],
            $sender
        );

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
     * The keys are the stored ones, underscores and all: they are what the reader of a
     * message sees, and what the panel shows beside the same event.
     *
     * @return string[]
     */
    private function values(mixed $values, NotificationSenderInterface $sender): array
    {
        if (!is_array($values)) {
            return [];
        }

        $lines = [];

        foreach ($values as $key => $value) {
            if (!is_string($key) || !is_scalar($value)) {
                continue;
            }

            $lines[] = sprintf(
                '• %s: %s',
                $sender->escape(str_replace('_', ' ', $key)),
                $sender->escape($this->readable($key, $value))
            );
        }

        return $lines;
    }

    /**
     * @param WatcherIncidentEventGroupObject[] $groups
     *
     * @return string[]
     */
    private function traces(array $groups, NotificationSenderInterface $sender): array
    {
        $lines = [];

        foreach (array_slice($groups, 0, self::MAX_GROUPS) as $group) {
            $lines[] = '• ' . $sender->escape(
                implode(' ', [$group->type, ...$group->tags])
            );

            $line = sprintf(
                '  %d trace%s',
                $group->count,
                $group->count === 1 ? '' : 's'
            );

            // Only the slow-traces watcher has a maximum to name; the groups of a
            // counting watcher carry a count and nothing else.
            if (!is_null($group->durationMax)) {
                $line .= ', up to ' . $sender->escape($this->readable('duration_max', $group->durationMax));
            }

            $lines[] = $line;

            if (!is_null($group->slowestTraceId)) {
                $lines[] = '  ' . $sender->code($sender->escape($group->slowestTraceId));
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
