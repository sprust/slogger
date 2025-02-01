<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Modules\Trace\Contracts\Repositories\TraceHubInvalidRepositoryInterface;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;

readonly class TraceHubInvalidRepository implements TraceHubInvalidRepositoryInterface
{
    public function __construct(
        private Collection $collection
    ) {
    }

    public function createMany(array $invalidTraceHubs): void
    {
        $createdAt = new UTCDateTime(now());

        $operations = [];

        foreach ($invalidTraceHubs as $invalidTraceHub) {
            $operations[] = [
                'insertOne' => [
                    [
                        'tid' => $invalidTraceHub->traceId,
                        'doc' => $invalidTraceHub->document,
                        'err' => $invalidTraceHub->error,
                        'cat' => $createdAt,
                    ],
                ],
            ];
        }

        $this->collection->bulkWrite($operations);
    }
}
