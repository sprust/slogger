<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Checkers;

use App\Modules\Watcher\Enums\WatcherTypeEnum;

/**
 * Which checker answers for which type.
 *
 * A match over the enum rather than a map built at boot, so that adding a case without a
 * checker does not compile.
 */
readonly class WatcherCheckerRegistry
{
    public function __construct(
        private BufferOverflowChecker $bufferOverflowChecker,
        private InvalidBufferGrownChecker $invalidBufferGrownChecker,
        private NoNewTracesChecker $noNewTracesChecker,
        private TracesSpikeChecker $tracesSpikeChecker,
        private SlowTracesChecker $slowTracesChecker
    ) {
    }

    public function for(WatcherTypeEnum $type): WatcherCheckerInterface
    {
        return match ($type) {
            WatcherTypeEnum::BufferOverflow     => $this->bufferOverflowChecker,
            WatcherTypeEnum::InvalidBufferGrown => $this->invalidBufferGrownChecker,
            WatcherTypeEnum::NoNewTraces        => $this->noNewTracesChecker,
            WatcherTypeEnum::TracesSpike        => $this->tracesSpikeChecker,
            WatcherTypeEnum::SlowTraces         => $this->slowTracesChecker,
        };
    }
}
