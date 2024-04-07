<?php

namespace App\Modules\TraceCleaner\Repositories;

use App\Models\Traces\TraceTree;
use App\Modules\TraceCleaner\Repositories\Interfaces\TraceTreeRepositoryInterface;

class TraceTreeRepository implements TraceTreeRepositoryInterface
{
    public function delete(array $traceIds): int
    {
        return TraceTree::query()->whereIn('traceId', $traceIds)->delete();
    }
}
