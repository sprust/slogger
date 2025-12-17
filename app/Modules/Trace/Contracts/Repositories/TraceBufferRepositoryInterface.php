<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Repositories;

use App\Modules\Trace\Parameters\TraceCreateParameters;
use App\Modules\Trace\Parameters\TraceUpdateParameters;
use App\Modules\Trace\Repositories\Dto\Trace\TraceBuffersDto;

interface TraceBufferRepositoryInterface
{
    public function create(TraceCreateParameters $trace): void;

    public function update(TraceUpdateParameters $trace): bool;

    public function findForHandling(int $page, int $perPage): TraceBuffersDto;

    public function markAsHandled(array $traceIds): void;

    /**
     * @param string[] $traceIds
     */
    public function delete(array $traceIds): int;
}
