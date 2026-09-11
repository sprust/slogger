<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Enums;

enum WatcherTypeEnum: string
{
    case BufferOverflow = 'bufferOverflow';
    case InvalidBufferGrown = 'invalidBufferGrown';
    case NoNewTraces = 'noNewTraces';
    case ManyTraces = 'manyTraces';
    case SlowTraces = 'slowTraces';
    case LogErrors = 'logErrors';

    /**
     * What the form offers before anybody has an opinion. The stored value is the
     * watcher's own from then on.
     */
    public function defaultCooldownSeconds(): int
    {
        return match ($this) {
            self::BufferOverflow, self::InvalidBufferGrown, self::NoNewTraces, self::LogErrors => 600,
            self::ManyTraces, self::SlowTraces => 300,
        };
    }
}
