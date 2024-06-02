<?php

namespace App\Modules\Trace\Repositories\Interfaces;

interface CleanerTraceTreeRepositoryInterface
{
    /**
     * @param string[] $traceIds
     *
     * @return int - number of deleted records
     */
    public function delete(array $traceIds): int;
}
