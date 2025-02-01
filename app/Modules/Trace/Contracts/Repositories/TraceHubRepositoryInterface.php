<?php

namespace App\Modules\Trace\Contracts\Repositories;

use App\Modules\Trace\Parameters\TraceCreateParameters;
use App\Modules\Trace\Parameters\TraceUpdateParameters;
use App\Modules\Trace\Repositories\Dto\Trace\TraceHubDto;
use Illuminate\Support\Carbon;

interface TraceHubRepositoryInterface
{
    public function create(TraceCreateParameters $trace): void;

    public function update(TraceUpdateParameters $trace): bool;

    /**
     * @return TraceHubDto[]
     */
    public function findForHandling(int $page, int $perPage, Carbon $deadTimeLine): array;

    /**
     * @param string[] $traceIds
     */
    public function delete(array $traceIds): int;
}
