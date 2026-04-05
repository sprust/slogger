<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Events;

readonly class TraceTreeCacheBuildRequestedEvent
{
    public function __construct(
        public string $rootTraceId,
        public string $version,
    ) {
    }
}
