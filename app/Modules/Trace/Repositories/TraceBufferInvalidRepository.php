<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Modules\Trace\Repositories\Dto\Buffer\TraceBufferInvalidDto;
use MongoDB\BSON\UTCDateTime;
use SConcur\Features\Mongodb\Connection\Collection;

readonly class TraceBufferInvalidRepository
{
    public function __construct(
        private Collection $collection
    ) {
    }

    /**
     * @param TraceBufferInvalidDto[] $invalidTraceBuffers
     */
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
