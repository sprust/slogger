<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities;

/**
 * What a checker saw when it decided the watcher has something to say.
 *
 * The payload is shapeless because every type reports its own numbers, and the panel only
 * renders them. What every one of them owes the reader is the value it measured beside the
 * threshold it crossed — a bare "it fired" is not worth an incident.
 */
readonly class WatcherTriggerObject
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public array $payload
    ) {
    }
}
