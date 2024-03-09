<?php

namespace App\Modules\TraceCleaner\Repositories;

use App\Models\Traces\Trace;
use App\Modules\TraceCleaner\Repositories\Contracts\TraceRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use MongoDB\BSON\UTCDateTime;

class TraceRepository implements TraceRepositoryInterface
{
    public function findIds(int $limit, Carbon $loggedAtTo, ?string $type, array $excludedTypes): array
    {
        return Trace::query()
            ->where('loggedAt', '<=', new UTCDateTime($loggedAtTo))
            ->when($type, fn(Builder $query) => $query->where('type', $type))
            ->when(is_null($type) && $excludedTypes, fn(Builder $query) => $query->whereNotIn('type', $excludedTypes))
            ->take($limit)
            ->pluck('traceId')
            ->toArray();
    }

    public function delete(array $traceIds): int
    {
        return Trace::query()->whereIn('traceId', $traceIds)->delete();
    }
}
