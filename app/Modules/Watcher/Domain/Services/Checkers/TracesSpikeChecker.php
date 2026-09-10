<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Checkers;

use App\Modules\Watcher\Domain\Services\WatcherTimelineAnalyzer;
use App\Modules\Watcher\Entities\Settings\TracesSpikeSettingsObject;
use App\Modules\Watcher\Entities\Events\TracesSpikeEventMeasuredObject;
use App\Modules\Watcher\Entities\Events\TracesSpikeEventPayloadObject;
use App\Modules\Watcher\Entities\Events\TracesSpikeEventSettingsObject;
use App\Modules\Watcher\Entities\Events\WatcherEventPayloadInterface;
use App\Modules\Watcher\Entities\WatcherCheckContextObject;
use App\Modules\Watcher\Entities\WatcherIncidentEventGroupObject;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Entities\WatcherTimelineGroupObject;
use App\Modules\Watcher\Entities\WatcherTimelineObject;
use App\Modules\Watcher\Repositories\WatcherTimelineRepository;
use Illuminate\Support\Carbon;

/**
 * The recent window carries far more traces than the stretch before it.
 *
 * Both sides are compared as traces per minute, not as totals: the window and the
 * baseline are different lengths, and comparing their sums would call every baseline
 * longer than the window a spike.
 */
readonly class TracesSpikeChecker implements WatcherCheckerInterface
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

        if (!$settings instanceof TracesSpikeSettingsObject) {
            return null;
        }

        $to           = $this->analyzer->windowEnd($context->now);
        $windowFrom   = $to->clone()->subMinutes($settings->windowMinutes);
        $baselineFrom = $windowFrom->clone()->subMinutes($settings->baselineMinutes);

        // The baseline has to be real, not merely absent: a watcher that has been
        // collecting for ten minutes has no hour to compare against.
        if (is_null($watcher->collectSince) || $watcher->collectSince->gt($baselineFrom)) {
            return null;
        }

        $timeline = $this->timelineRepository->find($watcher->id);

        // A line whose head the receiver dropped answers a shorter question than the one
        // asked: the buckets that survived are divided by the whole baseline either way,
        // which understates the rate and turns steady traffic into a rise — every cooldown,
        // for as long as the watcher exists. The settings are capped well inside what the
        // cap holds, but the cap counts elements and a busy watcher spends several on one
        // moment, so this has to be read rather than assumed.
        if ($this->analyzer->startsAfter($timeline, $baselineFrom)) {
            return null;
        }

        $baselineCount = $this->analyzer->countIn($timeline, $baselineFrom, $windowFrom);

        $baselineRate = $baselineCount / $settings->baselineMinutes;

        // Nothing to grow from. Against an empty baseline any traffic at all is an
        // infinite rise, which would make this fire the moment a quiet service wakes up —
        // an arrival, not a spike.
        if ($baselineRate <= 0) {
            return null;
        }

        $windowCount = $this->analyzer->countIn($timeline, $windowFrom, $to);

        $windowRate = $windowCount / $settings->windowMinutes;

        $growthPercent = ($windowRate - $baselineRate) / $baselineRate * 100;

        if ($growthPercent < $settings->growthPercent) {
            return null;
        }

        return new TracesSpikeEventPayloadObject(
            settings: new TracesSpikeEventSettingsObject(
                windowMinutes: $settings->windowMinutes,
                baselineMinutes: $settings->baselineMinutes,
                growthPercent: $settings->growthPercent
            ),
            measured: new TracesSpikeEventMeasuredObject(
                windowCount: $windowCount,
                windowPerMinute: round($windowRate, 2),
                baselinePerMinute: round($baselineRate, 2),
                growthPercent: round($growthPercent, 2)
            ),
            groups: $this->reportGroups($timeline, $windowFrom, $to)
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
            // Nothing timed here: a spike is a count, and the line's durations belong to
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
