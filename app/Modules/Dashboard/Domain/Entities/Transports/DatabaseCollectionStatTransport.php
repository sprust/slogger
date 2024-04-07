<?php

namespace App\Modules\Dashboard\Domain\Entities\Transports;

use App\Modules\Dashboard\Domain\Entities\Objects\DatabaseCollectionStatObject;
use App\Modules\Dashboard\Repositories\Dto\DatabaseCollectionStatDto;
use App\Modules\Dashboard\Repositories\Dto\DatabaseCollectionIndexStatDto;

class DatabaseCollectionStatTransport
{
    public static function toObject(DatabaseCollectionStatDto $collectionDto): DatabaseCollectionStatObject
    {
        return new DatabaseCollectionStatObject(
            name: $collectionDto->name,
            size: $collectionDto->size,
            indexesSize: $collectionDto->indexesSize,
            totalSize: $collectionDto->totalSize,
            count: $collectionDto->count,
            avgObjSize: $collectionDto->avgObjSize,
            indexes: array_map(
                fn(DatabaseCollectionIndexStatDto $indexDto) => DatabaseCollectionIndexStatTransport::toObject($indexDto),
                $collectionDto->indexes
            ),
        );
    }
}
