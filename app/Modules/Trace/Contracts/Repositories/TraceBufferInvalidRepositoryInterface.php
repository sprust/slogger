<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Repositories;

use App\Modules\Trace\Repositories\Dto\Buffer\TraceBufferInvalidDto;

interface TraceBufferInvalidRepositoryInterface
{
    /**
     * @param TraceBufferInvalidDto[] $invalidTraceBuffers
     */
    public function createMany(array $invalidTraceBuffers): void;
}
