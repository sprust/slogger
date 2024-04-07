<?php

namespace App\Modules\TraceCleaner\Repositories\Interfaces;

use Illuminate\Support\Carbon;

interface TraceRepositoryInterface
{
    /**
     * @param string[] $excludedTypes
     *
     * @return string[]
     */
    public function findIds(int $limit, Carbon $loggedAtTo, string $type, array $excludedTypes): array;

    /**
     * @param string[] $traceIds
     *
     * @return int - number of deleted records
     */
    public function delete(array $traceIds): int;
}
