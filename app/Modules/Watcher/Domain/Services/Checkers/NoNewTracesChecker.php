<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Checkers;

use App\Modules\Watcher\Domain\Services\WatcherTimelineAnalyzer;
use App\Modules\Watcher\Entities\Settings\NoNewTracesSettingsObject;
use App\Modules\Watcher\Entities\WatcherCheckContextObject;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Entities\WatcherTriggerObject;
use App\Modules\Watcher\Repositories\WatcherTimelineRepository;

/**
 * Nothing the watcher is about has arrived for a while.
 *
 * This is the one checker that reports an absence, so it is the one that has to be sure
 * the absence is real. A window that reaches back further than the watcher has been
 * collecting is empty because nobody was counting, and saying "no traces" about it would
 * make every newly created watcher fire once.
 */
readonly class NoNewTracesChecker implements WatcherCheckerInterface
{
    public function __construct(
        private WatcherTimelineRepository $timelineRepository,
        private WatcherTimelineAnalyzer $analyzer
    ) {
    }

    public function check(WatcherObject $watcher, WatcherCheckContextObject $context): ?WatcherTriggerObject
    {
        $settings = $watcher->settings;

        if (!$settings instanceof NoNewTracesSettingsObject) {
            return null;
        }

        $to   = $this->analyzer->windowEnd($context->now);
        $from = $to->clone()->subMinutes($settings->periodMinutes);

        if (is_null($watcher->collectSince) || $watcher->collectSince->gt($from)) {
            return null;
        }

        $count = $this->analyzer->countIn(
            $this->timelineRepository->find($watcher->id),
            $from,
            $to
        );

        if ($count > 0) {
            return null;
        }

        return new WatcherTriggerObject([
            'period_minutes' => $settings->periodMinutes,
            'window_from'    => $from->toDateTimeString(),
            'window_to'      => $to->toDateTimeString(),
        ]);
    }
}
