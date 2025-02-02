<?php

namespace App\Modules\Trace\Contracts\Repositories;

use App\Modules\Trace\Repositories\Dto\Trace\TraceBufferInvalidDto;

interface TraceBufferInvalidRepositoryInterface
{
    /**
     * @param TraceBufferInvalidDto[] $invalidTraceBuffers
     */
    public function createMany(array $invalidTraceBuffers): void;
}
