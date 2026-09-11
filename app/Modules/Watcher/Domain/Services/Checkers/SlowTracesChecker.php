<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Checkers;

use App\Modules\Watcher\Domain\Services\WatcherTimelineAnalyzer;
use App\Modules\Watcher\Entities\Settings\SlowTracesSettingsObject;
use App\Modules\Watcher\Entities\Events\SlowTracesEventMeasuredObject;
use App\Modules\Watcher\Entities\Events\SlowTracesEventPayloadObject;
use App\Modules\Watcher\Entities\Events\SlowTracesEventSettingsObject;
use App\Modules\Watcher\Entities\Events\WatcherEventPayloadInterface;
use App\Modules\Watcher\Entities\WatcherCheckContextObject;
use App\Modules\Watcher\Entities\WatcherIncidentEventGroupObject;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Entities\WatcherTimelineGroupObject;
use App\Modules\Watcher\Repositories\WatcherTimelineRepository;

/**
 * A trace the watcher is about ran longer than it should.
 *
 * What the line can answer is which shapes had a trace over the threshold, how slow the
 * slowest of each was, and its id — not how many crossed it. The rollup keeps a maximum
 * per shape, not a distribution, which is the price of the receiver knowing nothing about
 * thresholds. For "duration went over N seconds" it is enough, and the id is what makes
 * the event worth opening.
 */
readonly class SlowTracesChecker implements WatcherCheckerInterface
{
    /** How many shapes of the window are carried into the event. */
    private const int MAX_REPORTED_GROUPS = 5;

    public function __construct(
        private WatcherTimelineRepository $timelineRepository,
        private WatcherTimelineAnalyzer $analyzer
    ) {
    }

    public function check(WatcherObject $watcher, WatcherCheckContextObject $context): ?WatcherEventPayloadInterface
    {
        $settings = $watcher->settings;

        if (!$settings instanceof SlowTracesSettingsObject) {
            return null;
        }

        $to   = $this->analyzer->windowEnd($context->now);
        $from = $to->clone()->subMinutes($settings->windowMinutes);

        // No warm-up guard, unlike the watchers that report an absence: a window nobody
        // collected for holds no slow traces, so a short line can only keep this quiet.
        $groups = array_values(
            array_filter(
                $this->analyzer->groupsIn($this->timelineRepository->find($watcher->id), $from, $to),
                static fn(WatcherTimelineGroupObject $group): bool => $group->durationMax >= $settings->duration
            )
        );

        if (!count($groups)) {
            return null;
        }

        usort(
            $groups,
            static fn(WatcherTimelineGroupObject $a, WatcherTimelineGroupObject $b): int => $b->durationMax <=> $a->durationMax
        );

        return new SlowTracesEventPayloadObject(
            settings: new SlowTracesEventSettingsObject(
                duration: $settings->duration,
                windowMinutes: $settings->windowMinutes
            ),
            measured: new SlowTracesEventMeasuredObject(
                slowest: round($groups[0]->durationMax, 3)
            ),
            groups: array_map(
                static fn(WatcherTimelineGroupObject $group): WatcherIncidentEventGroupObject => new WatcherIncidentEventGroupObject(
                    serviceId: $group->serviceId,
                    type: $group->type,
                    tags: $group->tags,
                    count: $group->count,
                    durationMax: round($group->durationMax, 3),
                    slowestTraceId: $group->slowestTraceId,
                    slowestTraceLoggedAt: $group->slowestTraceLoggedAt?->toDateTimeString()
                ),
                array_slice($groups, 0, self::MAX_REPORTED_GROUPS)
            )
        );
    }
}
