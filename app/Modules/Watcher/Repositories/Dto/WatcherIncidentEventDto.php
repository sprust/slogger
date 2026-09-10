<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Repositories\Dto;

use Illuminate\Support\Carbon;

/**
 * An event document, as it is stored.
 *
 * The payload is the json that was written: reading it takes knowing which watcher type
 * wrote it, and that is the domain's. The repository's job ends at handing over what the
 * document says.
 */
readonly class WatcherIncidentEventDto
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $id,
        public string $incidentId,
        public array $payload,
        public Carbon $occurredAt
    ) {
    }
}
