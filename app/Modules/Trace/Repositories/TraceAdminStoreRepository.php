<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\TraceAdminStore;
use App\Modules\Common\Entities\PaginationInfoObject;
use App\Modules\Trace\Entities\Store\TraceAdminStoreObject;
use App\Modules\Trace\Entities\Store\TraceAdminStoresPaginationObject;
use Illuminate\Support\Carbon;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class TraceAdminStoreRepository
{
    public function create(
        string $title,
        int $storeVersion,
        string $storeDataHash,
        string $storeData,
        bool $auto
    ): TraceAdminStoreObject {
        $createdAt = Carbon::now();

        $result = TraceAdminStore::sconcur()->insertOne([
            'title'         => $title,
            'storeVersion'  => $storeVersion,
            'storeDataHash' => $storeDataHash,
            'storeData'     => $storeData,
            'auto'          => $auto,
            'createdAt'     => new UTCDateTime($createdAt),
        ]);

        return new TraceAdminStoreObject(
            id: (string) $result->insertedId,
            title: $title,
            storeVersion: $storeVersion,
            storeDataHash: $storeDataHash,
            storeData: $storeData,
            createdAt: $createdAt,
        );
    }

    public function find(
        int $page,
        int $perPage,
        int $version,
        ?string $searchQuery,
        bool $auto
    ): TraceAdminStoresPaginationObject {
        $filter = [
            'storeVersion' => $version,
            'auto'         => $auto,
        ];

        if ($searchQuery) {
            $filter['title'] = [
                '$regex'   => preg_quote($searchQuery, '/'),
                '$options' => 'i',
            ];
        }

        $total = TraceAdminStore::sconcur()->countDocuments($filter);

        $cursor = TraceAdminStore::sconcur()->find(
            filter: $filter,
            sort: ['createdAt' => -1],
            limit: $perPage,
            skip: ($page - 1) * $perPage,
        );

        $items = [];

        foreach ($cursor as $document) {
            $items[] = $this->documentToObject($document);
        }

        return new TraceAdminStoresPaginationObject(
            items: $items,
            paginationInfo: new PaginationInfoObject(
                total: $total,
                perPage: $perPage,
                currentPage: $page
            )
        );
    }

    public function delete(string $id): bool
    {
        return TraceAdminStore::sconcur()
            ->deleteOne(['_id' => new ObjectId($id)])
            ->deletedCount > 0;
    }

    /**
     * @param array<int|string, mixed> $document
     */
    private function documentToObject(array $document): TraceAdminStoreObject
    {
        return new TraceAdminStoreObject(
            id: (string) $document['_id'],
            title: $document['title'],
            storeVersion: $document['storeVersion'],
            storeDataHash: $document['storeDataHash'],
            storeData: $document['storeData'],
            createdAt: new Carbon($document['createdAt']->toDateTime()),
        );
    }
}
