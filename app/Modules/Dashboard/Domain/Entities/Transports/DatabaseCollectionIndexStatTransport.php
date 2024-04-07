<?php

namespace App\Modules\Dashboard\Domain\Entities\Transports;

use App\Modules\Dashboard\Domain\Entities\Objects\DatabaseCollectionIndexStatObject;
use App\Modules\Dashboard\Repositories\Dto\DatabaseCollectionIndexStatDto;

class DatabaseCollectionIndexStatTransport
{
    public static function toObject(DatabaseCollectionIndexStatDto $indexDto): DatabaseCollectionIndexStatObject
    {
        return new DatabaseCollectionIndexStatObject(
            name: $indexDto->name,
            size: $indexDto->size,
            usage: $indexDto->usage
        );
    }
}
