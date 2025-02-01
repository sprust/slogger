<?php

namespace App\Modules\Trace\Contracts\Repositories;

use App\Modules\Trace\Repositories\Dto\Trace\TraceHubInvalidDto;

interface TraceHubInvalidRepositoryInterface
{
    /**
     * @param TraceHubInvalidDto[] $invalidTraceHubs
     */
    public function createMany(array $invalidTraceHubs): void;
}
