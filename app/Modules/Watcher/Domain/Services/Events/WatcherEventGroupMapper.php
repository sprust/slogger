<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Events;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Watcher\Entities\WatcherIncidentEventGroupObject;
use Psr\Log\LoggerInterface;

/**
 * The shapes behind an event. One class for every type that has them, because a group
 * means the same thing whichever watcher reported it.
 */
readonly class WatcherEventGroupMapper
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    /**
     * A group document missing what a group is made of is dropped, not filled in.
     *
     * There is no such thing as service 0 or a trace of type "", and answering with one
     * would put a plausible-looking row in front of whoever is reading the incident. The
     * event keeps its numbers either way, so a group nobody can read costs a line in the
     * breakdown and a line in the log.
     *
     * @return WatcherIncidentEventGroupObject[]
     */
    public function read(mixed $groups): array
    {
        if (!is_array($groups)) {
            return [];
        }

        $result = [];

        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }

            /** @var array<string, mixed> $group */
            $serviceId = ArrayValueGetter::intNull($group, 'service_id');
            $type      = ArrayValueGetter::stringNull($group, 'type');
            $count     = ArrayValueGetter::intNull($group, 'count');

            if (is_null($serviceId) || is_null($type) || is_null($count)) {
                $this->logger->warning('A watcher incident event carries an unreadable group');

                continue;
            }

            $result[] = new WatcherIncidentEventGroupObject(
                serviceId: $serviceId,
                type: $type,
                tags: array_values(ArrayValueGetter::arrayStringNull($group, 'tags') ?? []),
                count: $count,
                durationMax: ArrayValueGetter::floatNull($group, 'duration_max'),
                slowestTraceId: ArrayValueGetter::stringNull($group, 'trace_id'),
                slowestTraceLoggedAt: ArrayValueGetter::stringNull($group, 'trace_logged_at')
            );
        }

        return $result;
    }

    /**
     * The timing keys are written even when they are null, so that a group document has
     * the same keys whichever watcher wrote it.
     *
     * @param WatcherIncidentEventGroupObject[] $groups
     *
     * @return array<int, array<string, mixed>>
     */
    public function toDocument(array $groups): array
    {
        return array_map(
            static fn(WatcherIncidentEventGroupObject $group): array => [
                'service_id'      => $group->serviceId,
                'type'            => $group->type,
                'tags'            => $group->tags,
                'count'           => $group->count,
                'duration_max'    => $group->durationMax,
                'trace_id'        => $group->slowestTraceId,
                'trace_logged_at' => $group->slowestTraceLoggedAt,
            ],
            $groups
        );
    }
}
