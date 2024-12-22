<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Dto\Trace\Profiling;

readonly class TraceProfilingDto
{
    /**
     * @param TraceProfilingItemDto[] $items
     */
    public function __construct(
        public string $mainCaller,
        public array $items
    ) {
    }
}
