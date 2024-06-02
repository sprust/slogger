<?php

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\TraceTree;
use App\Modules\Trace\Repositories\Interfaces\CleanerTraceTreeRepositoryInterface;

class CleanerTraceTreeRepository implements CleanerTraceTreeRepositoryInterface
{
    public function delete(array $traceIds): int
    {
        return TraceTree::query()->whereIn('traceId', $traceIds)->delete();
    }
}
