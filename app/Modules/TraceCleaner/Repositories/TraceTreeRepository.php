<?php

namespace App\Modules\TraceCleaner\Repositories;

use App\Models\Traces\TraceTree;
use App\Modules\TraceCleaner\Repositories\Contracts\TraceTreeRepositoryInterface;

class TraceTreeRepository implements TraceTreeRepositoryInterface
{
    public function delete(array $traceIds): int
    {
        return TraceTree::query()->whereIn('traceId', $traceIds)->delete();
    }
}
