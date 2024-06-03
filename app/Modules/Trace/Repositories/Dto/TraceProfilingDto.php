<?php

namespace App\Modules\Trace\Repositories\Dto;

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
