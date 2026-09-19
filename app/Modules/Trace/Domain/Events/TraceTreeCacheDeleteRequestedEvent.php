<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Events;

readonly class TraceTreeCacheDeleteRequestedEvent
{
    public function __construct(
        public string $rootTraceId,
        public ?string $buildVersion = null,
    ) {
    }
}
