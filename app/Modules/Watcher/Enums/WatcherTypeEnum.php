<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Enums;

enum WatcherTypeEnum: string
{
    case BufferOverflow = 'bufferOverflow';
    case InvalidBufferGrown = 'invalidBufferGrown';
    case NoNewTraces = 'noNewTraces';
    case TracesSpike = 'tracesSpike';
    case SlowTraces = 'slowTraces';

    /**
     * What the form offers before anybody has an opinion. The stored value is the
     * watcher's own from then on.
     */
    public function defaultCooldownSeconds(): int
    {
        return match ($this) {
            self::BufferOverflow, self::InvalidBufferGrown, self::NoNewTraces => 600,
            self::TracesSpike, self::SlowTraces => 300,
        };
    }
}
