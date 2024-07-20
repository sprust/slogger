<?php

namespace App\Modules\Dashboard\Domain\Entities\Transports;

use App\Modules\Dashboard\Domain\Entities\Objects\DatabaseStatObject;
use App\Modules\Dashboard\Repositories\Dto\DatabaseCollectionStatDto;
use App\Modules\Dashboard\Repositories\Dto\DatabaseStatDto;

class DatabaseStatTransport
{
    public static function toObject(DatabaseStatDto $databaseDto): DatabaseStatObject
    {
        return new DatabaseStatObject(
            name: $databaseDto->name,
            size: $databaseDto->size,
            memoryUsage: $databaseDto->memoryUsage,
            collections: array_map(
                fn(DatabaseCollectionStatDto $collectionDto) => DatabaseCollectionStatTransport::toObject(
                    $collectionDto
                ),
                $databaseDto->collections
            )
        );
    }
}
