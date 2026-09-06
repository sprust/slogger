<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Events;

/**
 * A dynamic index stopped being "in process", whichever way it went.
 *
 * Carries the outcome and nothing else. Whoever waits on this is waiting to repeat a
 * request that was refused with a 412 while the index was building — they need to know
 * that waiting is over, not what the index looks like.
 */
readonly class TraceDynamicIndexBuiltEvent
{
    public function __construct(
        public string $indexId,
        public bool $created,
        public ?string $error,
    ) {
    }
}
