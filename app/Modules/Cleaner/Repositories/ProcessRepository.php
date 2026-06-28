<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Repositories;

use App\Models\Traces\TraceClearingProcess;
use App\Modules\Cleaner\Entities\ProcessObject;
use Illuminate\Support\Carbon;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use Throwable;

class ProcessRepository
{
    /**
     * @return ProcessObject[]
     */
    public function find(int $page, int $perPage): array
    {
        $cursor = TraceClearingProcess::sconcur()->find(
            filter: [],
            sort: ['createdAt' => -1],
            limit: $perPage,
            skip: ($page - 1) * $perPage,
        );

        $processes = [];

        foreach ($cursor as $document) {
            $processes[] = $this->documentToObject($document);
        }

        return $processes;
    }

    public function exists(bool $clearedAtIsNull): ?ProcessObject
    {
        $cursor = TraceClearingProcess::sconcur()->find(
            filter: [
                'clearedAt' => $clearedAtIsNull
                    ? null
                    : ['$ne' => null],
            ],
            sort: ['createdAt' => -1],
            limit: 1,
        );

        foreach ($cursor as $document) {
            return $this->documentToObject($document);
        }

        return null;
    }

    public function create(): ProcessObject
    {
        $createdAt = Carbon::now();

        $result = TraceClearingProcess::sconcur()->insertOne([
            'clearedCollectionsCount' => 0,
            'clearedTracesCount'      => 0,
            'error'                   => null,
            'errorTrace'              => null,
            'clearedAt'               => null,
            'createdAt'               => new UTCDateTime($createdAt),
            'updatedAt'               => new UTCDateTime($createdAt),
        ]);

        return new ProcessObject(
            id: (string) $result->insertedId,
            clearedCollectionsCount: 0,
            clearedTracesCount: 0,
            error: null,
            clearedAt: null,
            createdAt: $createdAt,
            updatedAt: $createdAt
        );
    }

    public function update(
        string $processId,
        int $clearedCollectionsCount,
        int $clearedTracesCount,
        ?Carbon $clearedAt,
        ?Throwable $exception
    ): void {
        TraceClearingProcess::sconcur()->updateOne(
            filter: ['_id' => new ObjectId($processId)],
            update: [
                '$set' => [
                    'clearedCollectionsCount' => $clearedCollectionsCount,
                    'clearedTracesCount'      => $clearedTracesCount,
                    'error'                   => $exception ? ($exception->getMessage() ?: $exception::class) : null,
                    'errorTrace'              => $exception ? ($exception->getTraceAsString()) : null,
                    'clearedAt'               => $clearedAt ? new UTCDateTime($clearedAt) : null,
                    'updatedAt'               => new UTCDateTime(now()),
                ],
            ],
        );
    }

    public function deleteByProcessId(string $processId): void
    {
        TraceClearingProcess::sconcur()->deleteOne(['_id' => new ObjectId($processId)]);
    }

    /**
     * @param array<int|string, mixed> $document
     */
    private function documentToObject(array $document): ProcessObject
    {
        return new ProcessObject(
            id: (string) $document['_id'],
            clearedCollectionsCount: $document['clearedCollectionsCount'],
            clearedTracesCount: $document['clearedTracesCount'],
            error: $document['error'],
            clearedAt: isset($document['clearedAt'])
                ? new Carbon($document['clearedAt']->toDateTime())
                : null,
            createdAt: new Carbon($document['createdAt']->toDateTime()),
            updatedAt: new Carbon($document['updatedAt']->toDateTime())
        );
    }
}
