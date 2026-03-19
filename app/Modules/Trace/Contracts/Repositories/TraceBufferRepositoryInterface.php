<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Repositories;

use App\Modules\Trace\Parameters\TraceCreateParameters;
use App\Modules\Trace\Parameters\TraceUpdateParameters;
use App\Modules\Trace\Repositories\Dto\Buffer\TraceBuffersDto;

interface TraceBufferRepositoryInterface
{
    public function create(TraceCreateParameters $trace): void;

    public function update(TraceUpdateParameters $trace): bool;

    public function findForHandling(int $page, int $perPage): TraceBuffersDto;

    /**
     * @param string[] $ids
     */
    public function markAsHandled(array $ids): void;

    /**
     * @param string[] $ids
     */
    public function delete(array $ids): int;
}
