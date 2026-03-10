<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Dto\Buffer;

readonly class TraceBuffersDto
{
    /**
     * @param TraceBufferDto[]         $traces
     * @param CreatingTraceBufferDto[] $creatingTraces
     * @param UpdatingTraceBufferDto[] $updatingTraces
     * @param TraceBufferInvalidDto[]  $invalidTraces
     */
    public function __construct(
        public array $traces,
        public array $creatingTraces,
        public array $updatingTraces,
        public array $invalidTraces,
    ) {
    }
}
