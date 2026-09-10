<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Checkers;

use App\Modules\Watcher\Domain\Services\WatcherTimelineAnalyzer;
use App\Modules\Watcher\Entities\Events\ManyTracesEventMeasuredObject;
use App\Modules\Watcher\Entities\Events\ManyTracesEventPayloadObject;
use App\Modules\Watcher\Entities\Events\ManyTracesEventSettingsObject;
use App\Modules\Watcher\Entities\Events\WatcherEventPayloadInterface;
use App\Modules\Watcher\Entities\Settings\ManyTracesSettingsObject;
use App\Modules\Watcher\Entities\WatcherCheckContextObject;
use App\Modules\Watcher\Entities\WatcherIncidentEventGroupObject;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Entities\WatcherTimelineGroupObject;
use App\Modules\Watcher\Entities\WatcherTimelineObject;
use App\Modules\Watcher\Repositories\WatcherTimelineRepository;
use Illuminate\Support\Carbon;

/**
 * More traces than the limit arrived in the last stretch.
 *
 * A number, not a comparison with what came before. The rate against a baseline it used
 * to measure answered a question nobody could hold in their head — "90% above the last
 * hour" is a different number of traces every hour — and it could not fire at all until a
 * whole baseline had been collected.
 */
readonly class ManyTracesChecker implements WatcherCheckerInterface
{
    /** How many shapes of the window are carried into the event as evidence. */
    private const int MAX_REPORTED_GROUPS = 5;

    public function __construct(
        private WatcherTimelineRepository $timelineRepository,
        private WatcherTimelineAnalyzer $analyzer
    ) {
    }

    public function check(WatcherObject $watcher, WatcherCheckContextObject $context): ?WatcherEventPayloadInterface
    {
        $settings = $watcher->settings;

        if (!$settings instanceof ManyTracesSettingsObject) {
            return null;
        }

        $to   = $this->analyzer->windowEnd($context->now);
        $from = $to->clone()->subMinutes($settings->windowMinutes);

        // No warm-up guard, and none of the checks the baseline needed: a window nobody
        // collected for holds fewer traces than really arrived, never more, so a short
        // line can only keep this quiet. The watchers that report an absence are the ones
        // that have to wait.
        $timeline = $this->timelineRepository->find($watcher->id);

        $count = $this->analyzer->countIn($timeline, $from, $to);

        if ($count < $settings->threshold) {
            return null;
        }

        return new ManyTracesEventPayloadObject(
            settings: new ManyTracesEventSettingsObject(
                windowMinutes: $settings->windowMinutes,
                threshold: $settings->threshold
            ),
            measured: new ManyTracesEventMeasuredObject(
                windowCount: $count
            ),
            groups: $this->reportGroups($timeline, $from, $to)
        );
    }

    /**
     * @return WatcherIncidentEventGroupObject[]
     */
    private function reportGroups(
        WatcherTimelineObject $timeline,
        Carbon $from,
        Carbon $to
    ): array {
        $groups = $this->analyzer->groupsIn($timeline, $from, $to);

        usort(
            $groups,
            static fn(WatcherTimelineGroupObject $a, WatcherTimelineGroupObject $b): int => $b->count <=> $a->count
        );

        return array_map(
            // Nothing timed here: this watcher counts, and the line's durations belong to
            // the watcher that is about them.
            static fn(WatcherTimelineGroupObject $group): WatcherIncidentEventGroupObject => new WatcherIncidentEventGroupObject(
                serviceId: $group->serviceId,
                type: $group->type,
                tags: $group->tags,
                count: $group->count,
                durationMax: null,
                slowestTraceId: null
            ),
            array_slice($groups, 0, self::MAX_REPORTED_GROUPS)
        );
    }
}
