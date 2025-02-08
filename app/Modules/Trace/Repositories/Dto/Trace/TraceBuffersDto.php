<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Dto\Trace;

readonly class TraceBuffersDto
{
    /**
     * @param TraceBufferDto[]        $traces
     * @param TraceBufferInvalidDto[] $invalidTraces
     */
    public function __construct(
        public array $traces,
        public array $invalidTraces,
    ) {
    }
}
