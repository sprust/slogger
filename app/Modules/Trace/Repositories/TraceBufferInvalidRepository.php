<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Modules\Trace\Contracts\Repositories\TraceBufferInvalidRepositoryInterface;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;

readonly class TraceBufferInvalidRepository implements TraceBufferInvalidRepositoryInterface
{
    public function __construct(
        private Collection $collection
    ) {
    }

    public function createMany(array $invalidTraceBuffers): void
    {
        $createdAt = new UTCDateTime(now());

        $operations = [];

        foreach ($invalidTraceBuffers as $invalidTraceBuffer) {
            $operations[] = [
                'insertOne' => [
                    [
                        'tid' => $invalidTraceBuffer->traceId,
                        'doc' => $invalidTraceBuffer->document,
                        'err' => $invalidTraceBuffer->error,
                        'cat' => $createdAt,
                    ],
                ],
            ];
        }

        $this->collection->bulkWrite($operations);
    }
}
