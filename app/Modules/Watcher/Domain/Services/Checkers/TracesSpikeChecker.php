<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Checkers;

use App\Modules\Watcher\Domain\Services\WatcherTimelineAnalyzer;
use App\Modules\Watcher\Entities\Settings\TracesSpikeSettingsObject;
use App\Modules\Watcher\Entities\WatcherCheckContextObject;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Entities\WatcherTimelineGroupObject;
use App\Modules\Watcher\Entities\WatcherTimelineObject;
use App\Modules\Watcher\Entities\WatcherTriggerObject;
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

    public function check(WatcherObject $watcher, WatcherCheckContextObject $context): ?WatcherTriggerObject
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

        // The line has to cover the baseline, not merely start before the window. Buckets
        // are summed over whatever is there and divided by the full baseline, so a line
        // that reaches back only half of it halves the rate it reports — and steady
        // traffic then reads as a spike, for ever. The line can be short of the baseline
        // even for a watcher that has been collecting for days: the receiver caps it at
        // WatcherTimelineObject::MAX_DEPTH_MINUTES whatever the settings say.
        //
        // A line with nothing in it is not this case: an empty baseline is handled below,
        // and reporting an arrival as a spike is what that guard is for.
        $startsAt = $timeline->startsAt();

        if (!is_null($startsAt) && $startsAt->gt($baselineFrom)) {
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

        return new WatcherTriggerObject([
            'window_minutes'      => $settings->windowMinutes,
            'window_count'        => $windowCount,
            'window_per_minute'   => round($windowRate, 2),
            'baseline_minutes'    => $settings->baselineMinutes,
            'baseline_per_minute' => round($baselineRate, 2),
            'growth_percent'      => round($growthPercent, 2),
            'threshold_percent'   => $settings->growthPercent,
            'groups'              => $this->reportGroups($timeline, $windowFrom, $to),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
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
            static fn(WatcherTimelineGroupObject $group): array => [
                'service_id' => $group->serviceId,
                'type'       => $group->type,
                'tags'       => $group->tags,
                'count'      => $group->count,
            ],
            array_slice($groups, 0, self::MAX_REPORTED_GROUPS)
        );
    }
}
