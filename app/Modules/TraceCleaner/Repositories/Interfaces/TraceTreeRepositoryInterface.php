<?php

namespace App\Modules\TraceCleaner\Repositories\Interfaces;

interface TraceTreeRepositoryInterface
{
    /**
     * @param string[] $traceIds
     *
     * @return int - number of deleted records
     */
    public function delete(array $traceIds): int;
}
